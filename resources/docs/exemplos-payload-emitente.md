# Exemplos de Payload - API de Emitente

Este documento contém exemplos de payloads para todas as operações relacionadas ao cadastro e atualização de emitentes.

## 1. Cadastrar Emitente

**Endpoint:** `POST /api/emitente`  
**Autenticação:** Não requerida

### Payload Completo

```json
{
  "estado": {
    "nome": "São Paulo",
    "codigo_ibge": "35",
    "uf": "SP",
    "regiao": 3,
    "perc_aliq_icms_interna": 18.00
  },
  "cidade": {
    "nome": "São Paulo",
    "codigo_ibge": "3550308"
  },
  "emitente": {
    "razao_social": "Empresa Exemplo Ltda",
    "fantasia": "Exemplo",
    "cnpj": "12345678000190",
    "token_ibpt": "token_exemplo_123",
    "codigo_csc_id": 1,
    "codigo_csc": "CODIGO_CSC_123",
    "inscricao_estadual": "123456789012",
    "inscricao_municipal": "123456",
    "conteudo_logotipo": "data:image/png;base64,iVBORw0KG...",
    "conteudo_certificado": "-----BEGIN CERTIFICATE-----\nMIIF...\n-----END CERTIFICATE-----",
    "caminho_certificado": null,
    "senha_certificado": "senha123",
    "codigo_postal": "01310100",
    "logradouro": "Avenida Paulista",
    "numero": "1000",
    "bairro": "Bela Vista",
    "complemento": "Sala 100",
    "telefone": "11987654321",
    "email": "contato@exemplo.com.br",
    "regime_tributario": 1,
    "aliquota_geral_simples": 6.00,
    "ambiente_fiscal": 2
  },
  "user": {
    "name": "João Silva",
    "email": "joao@exemplo.com.br",
    "password": "senha123456"
  }
}
```

### Campos Obrigatórios

#### Estado
- `nome` (string, max: 100)
- `codigo_ibge` (string, max: 10)
- `uf` (string, exatamente 2 caracteres)
- `regiao` (integer, 1-5)
- `perc_aliq_icms_interna` (numeric, 0-100)

#### Cidade
- `nome` (string, max: 100)
- `codigo_ibge` (string, max: 20)

#### Emitente
- `razao_social` (string, max: 180)
- `cnpj` (string, exatamente 14 caracteres, único)
- `conteudo_certificado` (string) - conteúdo do certificado digital
- `senha_certificado` (string)
- `codigo_postal` (string, max: 20)
- `logradouro` (string, max: 150)
- `numero` (string, max: 20)
- `bairro` (string, max: 100)
- `email` (email, max: 150)
- `regime_tributario` (integer)
- `ambiente_fiscal` (integer, 1=Produção, 2=Homologação)

#### Usuário
- `name` (string, max: 255)
- `email` (email, único)
- `password` (string, mínimo 6 caracteres)

### Campos Opcionais do Emitente
- `fantasia`
- `token_ibpt`
- `codigo_csc_id`
- `codigo_csc`
- `inscricao_estadual`
- `inscricao_municipal`
- `conteudo_logotipo`
- `caminho_certificado`
- `complemento`
- `telefone`
- `aliquota_geral_simples`

### Resposta de Sucesso

```json
{
  "success": true,
  "status_code": 201,
  "data": {
    "message": "Emitente cadastrado com sucesso!",
    "emitente": {
      "id": 1,
      "razao_social": "Empresa Exemplo Ltda",
      "cnpj": "12345678000190",
      "email": "contato@exemplo.com.br"
    },
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@exemplo.com.br"
    }
  }
}
```

---

## 2. Visualizar Emitente

**Endpoint:** `GET /api/emitente`  
**Autenticação:** Requerida (Bearer Token)

### Resposta de Sucesso

```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "emitente": {
      "id": 1,
      "razao_social": "Empresa Exemplo Ltda",
      "fantasia": "Exemplo",
      "cnpj": "12345678000190",
      "token_ibpt": "token_exemplo_123",
      "codigo_csc_id": 1,
      "codigo_csc": "CODIGO_CSC_123",
      "inscricao_estadual": "123456789012",
      "inscricao_municipal": "123456",
      "codigo_postal": "01310100",
      "logradouro": "Avenida Paulista",
      "numero": "1000",
      "bairro": "Bela Vista",
      "complemento": "Sala 100",
      "telefone": "11987654321",
      "email": "contato@exemplo.com.br",
      "regime_tributario": 1,
      "aliquota_geral_simples": 6.00,
      "ambiente_fiscal": 2,
      "ambiente_fiscal_descricao": "Homologação",
      "caminho_certificado": null,
      "tem_certificado": true,
      "cidade": {
        "id": 1,
        "nome": "São Paulo",
        "codigo_ibge": "3550308",
        "estado": {
          "id": 1,
          "nome": "São Paulo",
          "uf": "SP",
          "codigo_ibge": "35"
        }
      }
    }
  }
}
```

---

## 3. Atualizar Dados do Emitente

**Endpoint:** `PUT /api/emitente` ou `PATCH /api/emitente`  
**Autenticação:** Requerida (Bearer Token)

### Payload Exemplo - Atualização Parcial

```json
{
  "razao_social": "Nova Razão Social Ltda",
  "fantasia": "Nova Fantasia",
  "email": "novoemail@exemplo.com.br",
  "telefone": "11999999999",
  "codigo_postal": "01310100",
  "logradouro": "Nova Rua",
  "numero": "2000",
  "bairro": "Novo Bairro",
  "complemento": "Sala 200",
  "regime_tributario": 3,
  "aliquota_geral_simples": 8.00
}
```

### Campos que Podem Ser Atualizados

- `razao_social`
- `fantasia`
- `cnpj` (deve ser único)
- `token_ibpt`
- `codigo_csc_id`
- `codigo_csc`
- `inscricao_estadual`
- `inscricao_municipal`
- `conteudo_logotipo`
- `codigo_postal`
- `logradouro`
- `numero`
- `bairro`
- `complemento`
- `telefone`
- `email`
- `regime_tributario`
- `aliquota_geral_simples`
- `cidade_id` (deve existir na tabela cidades)

### Resposta de Sucesso

```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "message": "Emitente atualizado com sucesso!",
    "emitente": {
      "id": 1,
      "razao_social": "Nova Razão Social Ltda",
      "cnpj": "12345678000190",
      "email": "novoemail@exemplo.com.br"
    }
  }
}
```

---

## 4. Atualizar Certificado Digital

**Endpoint:** `PUT /api/emitente/certificado` ou `PATCH /api/emitente/certificado`  
**Autenticação:** Requerida (Bearer Token)

### Payload Exemplo - Com Conteúdo do Certificado

```json
{
  "conteudo_certificado": "-----BEGIN CERTIFICATE-----\nMIIF...\n-----END CERTIFICATE-----",
  "senha_certificado": "novaSenha123"
}
```

### Payload Exemplo - Com Caminho do Certificado

```json
{
  "caminho_certificado": "/caminho/para/certificado.pfx",
  "senha_certificado": "novaSenha123"
}
```

### Campos Obrigatórios

- `senha_certificado` (string) - sempre obrigatório
- `conteudo_certificado` OU `caminho_certificado` (pelo menos um deve ser informado)

### Resposta de Sucesso

```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "message": "Certificado atualizado com sucesso!"
  }
}
```

---

## 5. Atualizar Ambiente Fiscal

**Endpoint:** `PUT /api/emitente/ambiente` ou `PATCH /api/emitente/ambiente`  
**Autenticação:** Requerida (Bearer Token)

### Payload Exemplo - Alterar para Produção

```json
{
  "ambiente_fiscal": 1
}
```

### Payload Exemplo - Alterar para Homologação

```json
{
  "ambiente_fiscal": 2
}
```

### Valores Aceitos

- `1` = Produção
- `2` = Homologação

### Resposta de Sucesso

```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "message": "Ambiente fiscal alterado para Produção com sucesso!",
    "ambiente_fiscal": 1,
    "ambiente_fiscal_descricao": "Produção"
  }
}
```

---

## Observações Importantes

1. **CNPJ**: Deve conter exatamente 14 caracteres (apenas números)
2. **Certificado Digital**: A senha é criptografada antes de ser armazenada no banco
3. **Ambiente Fiscal**: 
   - `1` = Produção (ambiente real)
   - `2` = Homologação (ambiente de testes)
4. **Estado e Cidade**: Se já existirem no banco (verificado pelo código IBGE), serão reutilizados
5. **Autenticação**: Todas as rotas de atualização requerem autenticação via Bearer Token (Sanctum)
6. **Validação**: Todos os campos são validados antes de serem processados

## Códigos de Resposta HTTP

- `200` - Sucesso (atualização/visualização)
- `201` - Criado com sucesso (cadastro)
- `400` - Erro de validação
- `401` - Não autenticado
- `404` - Emitente não encontrado
- `500` - Erro interno do servidor

