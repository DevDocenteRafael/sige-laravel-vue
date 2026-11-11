// @vitest-environment jsdom
import { describe, expect, it } from 'vitest'
import { renderizarMarkdownSeguro } from './markdownSeguro'

describe('renderizarMarkdownSeguro', () => {
  it('preserva a formatação Markdown permitida', () => {
    expect(renderizarMarkdownSeguro('**estoque**')).toContain('<strong>estoque</strong>')
  })

  it('remove elementos e atributos executáveis da resposta do chatbot', () => {
    const html = renderizarMarkdownSeguro(
      '<img src=x onerror=alert(1)><script>alert(1)</script><a href="javascript:alert(1)">link</a>'
    )

    expect(html).not.toContain('<script')
    expect(html).not.toContain('onerror')
    expect(html).not.toContain('javascript:')
  })
})
