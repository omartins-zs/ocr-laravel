# Fluxo Principal e Regras de Negocio

## Objetivo
Este documento descreve o fluxo operacional principal do sistema OCR e as regras de negocio essenciais para operacao segura, rastreavel e escalavel.

## Stack e Custo
- Laravel: gratuito
- Redis: gratuito
- PostgreSQL: gratuito
- OCRmyPDF: gratuito
- Tesseract: gratuito
- PaddleOCR (opcional): gratuito
- Frontend libs utilizadas: gratuitas

Conclusao: o sistema pode rodar 100% local e gratuito.

## Fluxo Principal (Fim a Fim)
1. Usuario faz upload de arquivo (`PDF`, `PNG`, `JPG`, `JPEG`).
2. Laravel valida o upload (extensao, mime type, tamanho maximo).
3. Laravel salva arquivo no storage local.
4. Laravel cria registro em `documents`.
5. Laravel cria registro de fila em `processing_jobs`.
6. Laravel despacha `ProcessDocumentJob` para fila Redis (`ocr`).
7. Worker processa em background e chama o servico Python OCR.
8. Python identifica tipo de arquivo e estrategia:
   - PDF com texto nativo: extrai texto direto (sem OCR pesado).
   - PDF escaneado: aplica OCRmyPDF + Tesseract.
   - Imagem: aplica Tesseract.
   - Fallback opcional: PaddleOCR em baixa confianca.
9. Python retorna JSON padronizado com:
   - texto bruto
   - texto normalizado
   - paginas
   - campos estruturados
   - metadados e confianca
10. Laravel persiste resultado em:
    - `document_extractions`
    - `extracted_fields`
    - `document_pages`
    - `document_versions`
    - `processing_logs`
11. Documento e concluido automaticamente como `approved` quando o OCR finaliza com sucesso.
12. Usuario pode consultar os campos extraidos e, se necessario, solicitar reprocessamento.

## Regras de Negocio Principais

## 1) Upload e Validacao
- Extensoes permitidas: `pdf`, `png`, `jpg`, `jpeg`.
- Mime types permitidos: `application/pdf`, `image/png`, `image/jpeg`.
- Tamanho maximo: 50MB por arquivo (ajustavel).
- Arquivo invalido deve ser recusado antes de entrar na fila.

## 2) Status de Documento
- `uploaded`: recebido no sistema.
- `queued`: aguardando processamento.
- `processing`: em processamento OCR.
- `approved`: processamento finalizado com sucesso.
- `failed`: erro de processamento.

## 3) Reprocessamento
- Documento pode ser reenviado para fila por acao manual.
- Reprocessamento cria novo `processing_job`.
- Versoes e historico devem ser mantidos para auditoria.

## 4) Confianca e Qualidade
- Cada campo extraido pode ter score de confianca.
- Em confianca baixa, recomenda-se reprocessamento para nova tentativa.

## 5) Rastreabilidade e Auditoria
- Todo processamento registra log em `processing_logs`.
- Cada tentativa de fila deve atualizar `attempts`, `stage`, `status`.
- Falhas devem registrar `error_message` e `error_code` quando disponivel.
- Alteracoes importantes geram versionamento (`document_versions`).

## 6) Permissoes (Perfil de Usuario)
- Perfis previstos: `admin`, `manager`, `reviewer`, `operator`, `viewer`.
- `admin/manager`: maior controle (configuracoes e operacao).
- `reviewer`: perfil legado, sem fluxo dedicado de revisao manual no momento.
- `operator`: upload e operacao de fila/reprocessamento.
- `viewer`: leitura.

## 7) Filas e Resiliencia
- Processamento OCR deve ser assincrono.
- Fila Redis (Docker) ou `database` (local), com retries controlados.
- Timeout por job para evitar processos travados.
- Observabilidade por `processing_logs`, `failed_jobs` e logs estruturados.

## 8) Tratamento de Erro
- Documento corrompido: status `failed`.
- Timeout de OCR: status `failed` + log detalhado.
- PDF protegido/problematico: log + falha controlada.
- Erro no servico Python: retorno tratavel no Laravel.

## 9) Dados Estruturados Esperados
Campos base previstos:
- nome
- CPF
- CNPJ
- RG
- numero do documento
- data de emissao
- data de vencimento
- valor
- endereco
- telefone
- e-mail
- razao social
- campos customizaveis por tipo de documento

## 10) Ambiente 100% Local e Gratuito
- Executar tudo com Docker Compose local.
- Storage local (sem S3).
- Sem dependencia de servico pago.
- Sem custo de licenca para OCR engine/banco/fila.
