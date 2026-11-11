import { marked } from 'marked'
import DOMPurify from 'dompurify'

marked.setOptions({ breaks: true })

export function renderizarMarkdownSeguro(texto) {
  if (!texto) return ''

  return DOMPurify.sanitize(marked.parse(texto), { USE_PROFILES: { html: true } })
}
