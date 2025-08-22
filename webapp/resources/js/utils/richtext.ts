import { generateJSON, generateHTML } from '@tiptap/html'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import Underline from '@tiptap/extension-underline'

// Shared TipTap extensions configuration used for HTML/JSON conversion
export const tiptapExtensions = [
  StarterKit,
  Underline,
  Image.configure({
    HTMLAttributes: {
      class: 'rounded-lg border border-gray-300 max-w-full h-auto',
    },
  }),
  Link.configure({
    openOnClick: false,
    HTMLAttributes: {
      class: 'text-blue-600 underline cursor-pointer',
    },
  }),
]

export function emptyDoc() {
  return { type: 'doc', content: [{ type: 'paragraph' }] }
}

export function isJSONLike(v: unknown): boolean {
  if (v && typeof v === 'object') return true
  if (typeof v !== 'string') return false
  const s = v.trim()
  if (!(s.startsWith('{') || s.startsWith('['))) return false
  try {
    JSON.parse(s)
    return true
  } catch {
    return false
  }
}

export function isHTMLLike(v: unknown): boolean {
  return typeof v === 'string' && /<\w+[\s>]/.test(v)
}

export function toTiptapJSON(input: unknown): any {
  try {
    if (input && typeof input === 'object') return input
    if (isJSONLike(input)) return JSON.parse(input as string)
    if (isHTMLLike(input)) return generateJSON(input as string, tiptapExtensions)
    return emptyDoc()
  } catch (e) {
    console.warn('toTiptapJSON fallback due to error:', e)
    return emptyDoc()
  }
}

export function toHTML(input: unknown): string {
  try {
    if (!input) return ''
    if (typeof input === 'string') return input
    // assume TipTap JSON
    return generateHTML(input as any, tiptapExtensions)
  } catch (e) {
    console.warn('toHTML fallback due to error:', e)
    return ''
  }
}

export function hasEditorContent(input: unknown): boolean {
  try {
    const json = toTiptapJSON(input)
    if (!json || typeof json !== 'object') return false
    if (json.type !== 'doc') return false
    const content = Array.isArray(json.content) ? json.content : []
    // Any non-empty content array counts as having content
    return content.length > 0
  } catch {
    return false
  }
}

