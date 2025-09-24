#!/bin/bash

# Monitor migration progress script
# This script provides real-time monitoring of the WorkOS to Supabase migration

set -e

# Configuration
APP_URL="${APP_URL:-http://localhost:8000}"
INTERVAL="${INTERVAL:-5}"  # Check interval in seconds
LOG_FILE="migration-monitor.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
log() {
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

log_info() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

# Check if application is healthy
check_health() {
    local response=$(curl -s -w "\n%{http_code}" "$APP_URL/health/basic" 2>/dev/null || echo "500")
    local http_code=$(echo "$response" | tail -n1)
    
    if [[ "$http_code" -eq 200 ]]; then
        local status=$(echo "$response" | head -n -1 | jq -r '.status' 2>/dev/null || echo "unknown")
        if [[ "$status" == "healthy" ]]; then
            return 0
        fi
    fi
    return 1
}

# Get migration statistics
get_migration_stats() {
    local response=$(curl -s "$APP_URL/health/migration" 2>/dev/null || echo '{"error": "Failed to fetch"}')
    echo "$response"
}

# Get authentication status
get_auth_status() {
    local response=$(curl -s "$APP_URL/health/authentication" 2>/dev/null || echo '{"error": "Failed to fetch"}')
    echo "$response"
}

# Display migration progress
display_progress() {
    local stats="$1"
    local total=$(echo "$stats" | jq -r '.total // 0')
    local migrated=$(echo "$stats" | jq -r '.migrated // 0')
    local pending=$((total - migrated))
    local percentage=$(echo "$stats" | jq -r '.progress.percentage // 0')
    local errors=$(echo "$stats" | jq -r '.errors_last_24h // 0')
    
    clear
    echo "======================================================"
    echo "      WORKOS TO SUPABASE MIGRATION MONITOR"
    echo "======================================================"
    echo
    echo -e "Status: ${GREEN}RUNNING${NC}"
    echo "Last Update: $(date '+%Y-%m-%d %H:%M:%S')"
    echo
    echo "MIGRATION PROGRESS"
    echo "------------------"
    echo "Total Users:      $total"
    echo "Migrated:         $migrated"
    echo "Pending:          $pending"
    echo "Progress:         ${percentage}%"
    echo
    
    # Progress bar
    local bar_width=50
    local filled=$((percentage * bar_width / 100))
    local empty=$((bar_width - filled))
    printf "["
    printf "%${filled}s" | tr " " "="
    printf "%${empty}s" | tr " " " "
    printf "] %d%%\n" "$percentage"
    echo
    
    if [[ "$errors" -gt 0 ]]; then
        echo -e "Errors (24h):    ${RED}$errors${NC}"
    else
        echo "Errors (24h):    $errors"
    fi
    
    local recent_activity=$(echo "$stats" | jq -r '.recent_activity // 0')
    echo "Recent Activity:  $recent_activity users in last 24h"
    
    local estimated=$(echo "$stats" | jq -r '.progress.estimated_completion // "Unknown"')
    echo "Est. Completion: $estimated"
    echo
    echo "------------------------------------------------------"
}

# Main monitoring loop
main() {
    log_info "Starting migration monitor..."
    log_info "Monitoring endpoint: $APP_URL"
    log_info "Check interval: ${INTERVAL}s"
    log_info "Log file: $LOG_FILE"
    echo
    
    # Initial health check
    if ! check_health; then
        log_error "Application health check failed!"
        exit 1
    fi
    log_success "Application health check passed"
    
    # Get initial auth status
    local auth_status=$(get_auth_status)
    local auth_mode=$(echo "$auth_status" | jq -r '.auth_mode // "unknown"')
    log_info "Authentication mode: $auth_mode"
    
    # Main loop
    while true; do
        # Check health
        if ! check_health; then
            log_error "Health check failed!"
            sleep "$INTERVAL"
            continue
        fi
        
        # Get and display migration stats
        local stats=$(get_migration_stats)
        if [[ "$stats" == *"error"* ]]; then
            log_error "Failed to fetch migration stats: $stats"
        else
            display_progress "$stats"
            
            # Check if migration is complete
            local total=$(echo "$stats" | jq -r '.total // 0')
            local migrated=$(echo "$stats" | jq -r '.migrated // 0')
            
            if [[ "$total" -gt 0 ]] && [[ "$migrated" -eq "$total" ]]; then
                log_success "MIGRATION COMPLETED! All users have been migrated."
                break
            fi
        fi
        
        # Check authentication status periodically
        if (( RANDOM % 20 == 0 )); then  # Check every ~20 iterations
            local current_auth=$(get_auth_status)
            local current_mode=$(echo "$current_auth" | jq -r '.auth_mode // "unknown"')
            if [[ "$current_mode" != "$auth_mode" ]]; then
                log_warning "Authentication mode changed: $auth_mode → $current_mode"
                auth_mode="$current_mode"
            fi
        fi
        
        sleep "$INTERVAL"
    done
    
    log_success "Migration monitoring complete!"
    echo
    echo "Final Migration Statistics:"
    echo "---------------------------"
    echo "$stats" | jq .
}

# Handle script termination
trap 'log_info "Monitoring stopped by user"; exit 0' INT TERM

# Check dependencies
if ! command -v curl &> /dev/null; then
    log_error "curl is required but not installed"
    exit 1
fi

if ! command -v jq &> /dev/null; then
    log_error "jq is required but not installed"
    exit 1
fi

# Run main function
main "$@"