import DOMPurify from 'dompurify';

/**
 * Sanitize HTML content to prevent XSS attacks
 * Allows only safe formatting tags commonly used in rich text editing
 */
export function sanitizeHtml(html: string): string {
  if (!html || typeof html !== 'string') {
    return '';
  }

  // Configure DOMPurify to allow only safe formatting tags
  const cleanHtml = DOMPurify.sanitize(html, {
    ALLOWED_TAGS: [
      'p', 'br', 'strong', 'b', 'em', 'i', 'u', 
      'ul', 'ol', 'li'
    ],
    ALLOWED_ATTR: [],
    KEEP_CONTENT: true,
    RETURN_DOM: false,
    RETURN_DOM_FRAGMENT: false,
    RETURN_DOM_IMPORT: false,
  });

  return cleanHtml;
}

/**
 * Strip all HTML tags and return plain text
 * Useful as a fallback when HTML rendering is not desired
 */
export function stripHtml(html: string): string {
  if (!html || typeof html !== 'string') {
    return '';
  }

  return DOMPurify.sanitize(html, { 
    ALLOWED_TAGS: [], 
    KEEP_CONTENT: true 
  });
}

/**
 * Get a safe preview of HTML content (first N characters, stripped of HTML)
 * Useful for card previews and summaries
 */
export function getHtmlPreview(html: string, maxLength: number = 120): string {
  const plainText = stripHtml(html);
  
  if (plainText.length <= maxLength) {
    return plainText;
  }
  
  return plainText.substring(0, maxLength).trim() + '...';
}