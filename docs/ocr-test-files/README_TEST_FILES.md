# OCR Test Files

Este pacote contem arquivos para validar os fluxos principais do sistema OCR.

## Arquivos

1. `01-pdf-texto-nativo-fatura.pdf`
- Deve extrair texto direto (sem OCR pesado).
- Campos chave: nome, CPF, CNPJ, valor, datas, endereco, telefone e e-mail.

2. `02-pdf-escaneado-contrato.pdf`
- PDF apenas com imagem (deve acionar OCR).
- Bom para validar pipeline OCRmyPDF + Tesseract.

3. `03-imagem-rg-frente.png`
- Imagem simples para extra??o de RG/CPF.

4. `04-imagem-recibo.jpg`
- Imagem com leve rotacao e ruido.

5. `05-imagem-fatura.jpeg`
- Imagem com blur leve para testar robustez.

6. `06-pdf-multipaginas-misto.pdf`
- PDF multipagina: pagina 1 nativa + pagina 2 escaneada.

7. `07-imagem-baixa-qualidade.jpg`
- Caso dificil com bastante ruido (esperado confidence menor).

8. `08-pdf-corrompido.pdf`
- Arquivo invalido para testar falha controlada e logs.

## Sugestao de uso
- Teste upload de cada arquivo e acompanhe status na fila.
- Valide campos extraidos e score de confianca.
- Reprocesse o arquivo 07 para comparar melhorias.
- Garanta erro amigavel para o arquivo 08.
