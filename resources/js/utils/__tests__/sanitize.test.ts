import { sanitizeHtml, stripHtml, truncateHtml } from '../sanitize';

// Mock DOMPurify for testing
const mockSanitize = jest.fn();
jest.mock('dompurify', () => ({
  sanitize: mockSanitize,
}));

describe('sanitizeHtml', () => {
  beforeEach(() => {
    mockSanitize.mockClear();
  });

  test('should handle empty input', () => {
    const result = sanitizeHtml('');
    expect(result).toBe('');
  });

  test('should call DOMPurify with correct configuration', () => {
    mockSanitize.mockReturnValue('<p>Clean HTML</p>');
    
    const result = sanitizeHtml('<p>Test HTML</p>');
    
    expect(mockSanitize).toHaveBeenCalledWith('<p>Test HTML</p>', {
      ALLOWED_TAGS: ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li'],
      ALLOWED_ATTR: [],
      KEEP_CONTENT: true,
      RETURN_DOM_FRAGMENT: false,
      RETURN_DOM: false,
    });
    expect(result).toBe('<p>Clean HTML</p>');
  });
});

describe('stripHtml', () => {
  test('should handle empty input', () => {
    const result = stripHtml('');
    expect(result).toBe('');
  });

  test('should strip HTML tags and return plain text', () => {
    // Mock DOM creation
    const mockDiv = {
      innerHTML: '',
      textContent: 'Plain text content',
      innerText: 'Plain text content',
    };
    jest.spyOn(document, 'createElement').mockReturnValue(mockDiv as any);
    mockSanitize.mockReturnValue('<p>Plain text content</p>');

    const result = stripHtml('<p><strong>Plain</strong> text content</p>');
    
    expect(result).toBe('Plain text content');
  });
});

describe('truncateHtml', () => {
  test('should return full content if under maxLength', () => {
    mockSanitize.mockReturnValue('<p>Short</p>');
    // Mock DOM for stripHtml
    const mockDiv = {
      innerHTML: '',
      textContent: 'Short',
      innerText: 'Short',
    };
    jest.spyOn(document, 'createElement').mockReturnValue(mockDiv as any);

    const result = truncateHtml('<p>Short</p>', 120);
    
    expect(result).toBe('<p>Short</p>');
  });

  test('should truncate content if over maxLength', () => {
    const longText = 'This is a very long text that exceeds the maximum length limit and should be truncated';
    const mockDiv = {
      innerHTML: '',
      textContent: longText,
      innerText: longText,
    };
    jest.spyOn(document, 'createElement').mockReturnValue(mockDiv as any);
    mockSanitize.mockReturnValue(`<p>${longText}</p>`);

    const result = truncateHtml(`<p>${longText}</p>`, 50);
    
    expect(result).toBe('This is a very long text that exceeds the maxim...');
  });
});