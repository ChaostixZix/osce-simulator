import React from 'react';

/**
 * QueueIndicator Component
 * Displays assessment queue status with visual indicators
 */
export default function QueueIndicator({ status, className = '', compact = false }) {
  if (!status || status.status === 'not_queued') {
    return null;
  }

  const getStatusInfo = () => {
    switch (status.status) {
      case 'queued':
        return {
          icon: '⏳',
          color: 'text-yellow-600 bg-yellow-50 border-yellow-200',
          title: 'Queued for Assessment',
          message: status.status_message || `Position ${status.queue_position || '?'} in queue`,
          showProgress: false,
        };
      
      case 'in_progress':
        return {
          icon: '🔄',
          color: 'text-blue-600 bg-blue-50 border-blue-200',
          title: 'Assessment in Progress',
          message: status.current_area ? `Analyzing ${status.current_area}...` : 'Processing...',
          showProgress: true,
        };
      
      case 'completed':
        return {
          icon: '✅',
          color: 'text-green-600 bg-green-50 border-green-200',
          title: 'Assessment Complete',
          message: 'Assessment completed successfully',
          showProgress: false,
        };
      
      case 'failed':
        return {
          icon: '❌',
          color: 'text-red-600 bg-red-50 border-red-200',
          title: 'Assessment Failed',
          message: status.status_message || 'Assessment encountered an error',
          showProgress: false,
        };
      
      default:
        return {
          icon: '❓',
          color: 'text-gray-600 bg-gray-50 border-gray-200',
          title: 'Unknown Status',
          message: status.status_message || 'Status unknown',
          showProgress: false,
        };
    }
  };

  const statusInfo = getStatusInfo();

  if (compact) {
    return (
      <div className={`inline-flex items-center space-x-2 px-3 py-1 rounded-full border text-sm ${statusInfo.color} ${className}`}>
        <span className="text-sm">{statusInfo.icon}</span>
        <span className="font-medium">{statusInfo.title}</span>
        {status.status === 'queued' && status.queue_position && (
          <span className="text-xs opacity-75">#{status.queue_position}</span>
        )}
      </div>
    );
  }

  return (
    <div className={`rounded-lg border p-4 ${statusInfo.color} ${className}`}>
      <div className="flex items-start space-x-3">
        <div className="flex-shrink-0">
          {status.status === 'in_progress' ? (
            <div className="animate-spin rounded-full h-6 w-6 border-2 border-current border-t-transparent"></div>
          ) : (
            <span className="text-2xl">{statusInfo.icon}</span>
          )}
        </div>
        
        <div className="flex-1 min-w-0">
          <div className="flex items-center justify-between">
            <h3 className="font-semibold text-lg">{statusInfo.title}</h3>
            {status.status === 'queued' && status.estimated_wait_time_minutes && (
              <span className="text-sm font-medium opacity-75">
                ~{status.estimated_wait_time_minutes}min
              </span>
            )}
          </div>
          
          <p className="text-sm mt-1 opacity-90">{statusInfo.message}</p>
          
          {/* Queue Details */}
          {status.status === 'queued' && (
            <div className="mt-2 space-y-1">
              {status.queue_position && (
                <div className="text-sm opacity-75">
                  Position: <span className="font-medium">#{status.queue_position}</span>
                </div>
              )}
              {status.estimated_wait_time_minutes && (
                <div className="text-sm opacity-75">
                  Estimated wait: <span className="font-medium">{status.estimated_wait_time_minutes} minutes</span>
                </div>
              )}
            </div>
          )}
          
          {/* Progress Bar */}
          {statusInfo.showProgress && status.progress_percentage !== undefined && (
            <div className="mt-3">
              <div className="flex justify-between items-center mb-1">
                <span className="text-sm font-medium">Progress</span>
                <span className="text-sm">{status.progress_percentage}%</span>
              </div>
              <div className="w-full bg-white bg-opacity-50 rounded-full h-2">
                <div 
                  className="bg-current h-2 rounded-full transition-all duration-300"
                  style={{ width: `${status.progress_percentage}%` }}
                ></div>
              </div>
              {status.completed_areas && status.total_areas && (
                <div className="text-xs mt-1 opacity-75">
                  {status.completed_areas} of {status.total_areas} areas completed
                </div>
              )}
            </div>
          )}
          
          {/* Timestamp */}
          {status.timestamp && (
            <div className="text-xs mt-2 opacity-75">
              Last updated: {new Date(status.timestamp).toLocaleTimeString()}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

/**
 * QueueBadge Component
 * Simple badge variant for inline use
 */
export function QueueBadge({ status, className = '' }) {
  if (!status || status.status === 'not_queued') {
    return null;
  }

  const getBadgeInfo = () => {
    switch (status.status) {
      case 'queued':
        return {
          text: status.queue_position ? `Queue #${status.queue_position}` : 'Queued',
          color: 'bg-yellow-100 text-yellow-800',
        };
      case 'in_progress':
        return {
          text: 'Processing...',
          color: 'bg-blue-100 text-blue-800',
        };
      case 'completed':
        return {
          text: 'Complete',
          color: 'bg-green-100 text-green-800',
        };
      case 'failed':
        return {
          text: 'Failed',
          color: 'bg-red-100 text-red-800',
        };
      default:
        return {
          text: 'Unknown',
          color: 'bg-gray-100 text-gray-800',
        };
    }
  };

  const badgeInfo = getBadgeInfo();

  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeInfo.color} ${className}`}>
      {badgeInfo.text}
    </span>
  );
}