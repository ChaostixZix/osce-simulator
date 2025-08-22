import DOMPurify from 'dompurify';

/**
 * Sanitizes HTML content to prevent XSS attacks
 * Allows only safe formatting tags for rich text content
 */
export function sanitizeHtml(html: string): string {
  if (!html) return '';
  
  // Configure DOMPurify to allow only safe formatting tags
  const clean = DOMPurify.sanitize(html, {
    ALLOWED_TAGS: [
      'p', 'br', 'strong', 'b', 'em', 'i', 'u', 
      'ul', 'ol', 'li'
    ],
    ALLOWED_ATTR: [],
    KEEP_CONTENT: true,
    RETURN_DOM_FRAGMENT: false,
    RETURN_DOM: false,
  });

  return clean;
}

/**
 * Strips all HTML tags and returns plain text
 * Useful for generating previews or summaries
 */
export function stripHtml(html: string): string {
  if (!html) return '';
  
  // Create a temporary div element to extract text content
  const temp = document.createElement('div');
  temp.innerHTML = sanitizeHtml(html);
  return temp.textContent || temp.innerText || '';
}

/**
 * Truncates HTML content to a specified length while preserving formatting
 * Useful for previews in timelines
 */
export function truncateHtml(html: string, maxLength: number = 120): string {
  const plainText = stripHtml(html);
  if (plainText.length <= maxLength) {
    return sanitizeHtml(html);
  }
  
  // If we need to truncate, just return the plain text truncated
  return plainText.substring(0, maxLength) + '...';
}