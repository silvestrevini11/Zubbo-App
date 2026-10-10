<div align="center">

<img src="-TCC-/public/imagem/LogooZ.png" alt="Logo do Zubbo" width="110" />

# Zubbo

### Encontre. Pratique. Conecte-se.

**Tecnologia, esporte e comunidade como instrumentos de inclusão e convivência social.**

**Trabalho de Conclusão de Curso (TCC)** · **Técnico em Desenvolvimento de Sistemas** · **2026**

</div>

---

## Sobre o projeto

O **Zubbo** é um projeto acadêmico de plataforma para aproximar pessoas interessadas em **praticar esportes de forma amadora, acessível e comunitária**. A aplicação ajuda a descobrir locais esportivos, encontrar eventos, formar equipes e estabelecer conexões com outras pessoas por meio de atividades físicas.

Mais do que organizar partidas, a proposta é facilitar a participação de quem encontra dificuldades para começar a praticar uma modalidade, conhecer grupos locais ou ingressar em ambientes esportivos acolhedores.

O projeto une desenvolvimento de sistemas, convivência comunitária e incentivo ao bem-estar. Não se apresenta como serviço de saúde nem substitui acompanhamento profissional.

## Instituição de ensino

- **Instituição:** Escola Técnica Estadual (**ETEC**), integrante da rede do **Centro Paula Souza (CPS)**.
- **Curso:** Técnico em Desenvolvimento de Sistemas.
- **Natureza do trabalho:** Trabalho de Conclusão de Curso.
- **Ano:** 2026.
- **Unidade da ETEC:** *a equipe poderá acrescentar a unidade específica*.
- **Turma e orientação:** *a preencher com os dados oficiais do TCC*.

O desenvolvimento do Zubbo envolve conhecimentos adquiridos no curso, como programação web, banco de dados, modelagem, interface, testes, documentação, segurança e organização de projetos de software.

## Equipe do projeto

O Zubbo é desenvolvido por **estudantes do curso Técnico em Desenvolvimento de Sistemas da ETEC**, com colaboração nas etapas de planejamento, prototipação, programação, modelagem de banco de dados, testes e apresentação acadêmica.

| Identificação | Informação |
|---|---|
| **Equipe** | Equipe de desenvolvimento do TCC Zubbo |
| **Integrantes** | *Nomes a serem confirmados pela equipe* |
| **Professor(a) orientador(a)** | *A preencher* |
| **ETEC / unidade** | *A preencher* |
| **Curso** | Técnico em Desenvolvimento de Sistemas |

> Os nomes e a unidade não foram informados de forma confirmada nesta documentação. Evitamos inserir dados pessoais ou atribuir autoria incorretamente; a equipe pode completar esta seção antes da apresentação.

## Tema

**O uso da tecnologia para promover a inclusão social e facilitar encontros esportivos comunitários.**

O Zubbo considera o esporte amador uma oportunidade de convivência, construção de vínculos e incentivo a hábitos saudáveis, especialmente quando pessoas encontram barreiras de acesso a espaços, informações ou grupos de prática.

## Problema e justificativa

Embora haja interesse em atividades esportivas, nem sempre é simples encontrar **onde praticar, com quem participar ou como ingressar em um grupo**.

Entre as dificuldades que motivam este TCC estão:

- **Informação dispersa** sobre locais e encontros esportivos na região;
- **Dificuldade de encontrar participantes** com interesse na mesma modalidade;
- **Custos ou barreiras de acesso** a determinadas opções de prática;
- **Ambientes excessivamente competitivos**, que podem afastar iniciantes;
- **Menos oportunidades de convivência presencial** e integração comunitária.

Por isso, o trabalho investiga como uma aplicação digital pode ajudar a tornar **encontros esportivos amadores mais acessíveis, organizados e acolhedores**.

## Proposta de solução

Desenvolver uma plataforma em que usuários possam **descobrir locais, organizar encontros e participar de eventos esportivos**, com ferramentas de comunicação e mecanismos de moderação.

O sistema foi pensado para conectar três elementos:

~~~text
PESSOAS  +  LOCAIS ESPORTIVOS  +  EVENTOS
                    |
              COMUNIDADE ZUBBO
~~~

Como recorte inicial do protótipo, a aplicação utiliza locais esportivos da região de **Diadema (SP)**, com a possibilidade de expansão para outras localidades.

## Objetivos do projeto

### Objetivo geral

**Desenvolver uma solução tecnológica que facilite a organização e a participação em encontros esportivos amadores, incentivando a integração social e ampliando o acesso à prática esportiva comunitária.**

### Objetivos específicos

1. **Reunir informações** sobre locais disponíveis para atividades esportivas.
2. **Permitir a criação e a descoberta de eventos** por modalidade, local, data e horário.
3. **Facilitar a participação em equipes**, com solicitações de vagas e organização de times.
4. **Estimular conexões entre usuários** por meio de perfis, amizades, conversas e grupos.
5. **Promover um ambiente organizado e responsável**, com denúncias e recursos de administração.
6. **Desenvolver uma interface acessível e responsiva**, utilizável também em dispositivos móveis.
7. **Aplicar práticas de engenharia de software**, incluindo modelagem relacional, autenticação, controle de acesso, testes e documentação.
8. **Preparar a evolução do projeto** para um aplicativo Android, inicialmente aproveitando a versão web.

## Público-alvo

Pessoas interessadas em praticar esportes amadores, conhecer outros participantes, encontrar atividades e organizar encontros em sua comunidade — incluindo iniciantes e quem deseja uma alternativa mais social e menos voltada à competição.

## Recursos da aplicação

| Área | O que o Zubbo oferece |
|---|---|
| **Perfis** | Cadastro, preferências esportivas e informações pessoais controladas |
| **Locais** | Pesquisa e visualização de espaços esportivos no mapa |
| **Eventos** | Criação e divulgação de encontros por esporte, local e horário |
| **Equipes** | Solicitação e aprovação de vagas, com divisão em times |
| **Comunicação** | Amizades, mensagens privadas, grupos e notificações |
| **Comunidade segura** | Denúncias, moderação e painel administrativo |
| **Qualidade** | Testes automatizados, validações de segurança e logs técnicos |

## Tecnologias utilizadas

| Camada | Tecnologia |
|---|---|
| Interface | HTML, CSS e JavaScript |
| Aplicação | PHP |
| Persistência | MySQL e PDO |
| Mapa | Mapbox |
| E-mail | Resend / PHPMailer |
| Hospedagem de demonstração | Railway |
| Versionamento e testes | GitHub e GitHub Actions |
| Próxima etapa mobile | Android Studio e Kotlin, com WebView experimental |

## Estado do desenvolvimento

O projeto possui uma **versão web funcional de demonstração**, com os principais módulos de cadastro, pesquisa, mapa, eventos, conversas e administração.

A equipe registrou **39 verificações funcionais concluídas** no ambiente de demonstração, incluindo fluxos de login, perfil, mensagens, eventos, denúncias e uso pelo navegador do celular.

Isso representa uma homologação **funcional do protótipo**, não uma certificação de segurança nem uma autorização automática para distribuição pública. Melhorias de privacidade, monitoramento, desempenho, armazenamento e revisão independente continuam previstas.

A branch de desenvolvimento e validação é **teste-railway**. A **main** permanece separada até a decisão de integração.

## Próximos passos

- Aperfeiçoar a estabilidade, o desempenho e a proteção de dados.
- Evoluir monitoramento, armazenamento persistente e testes de carga.
- Criar um **APK experimental para Android**, usando a interface web hospedada.
- Validar a experiência em aparelhos reais.
- Avaliar a expansão de locais, modalidades e comunidades atendidas.

## Documentação e funcionamento do aplicativo

**Para saber como o código funciona, instalar o projeto, criar o banco, configurar o Railway, consultar logs e executar testes, acesse a documentação técnica:**

### [📘 Documentação técnica e funcionamento do Zubbo →](./-TCC-/README.md)

Documentos complementares:

- [Segurança da aplicação](./-TCC-/SECURITY.md)
- [Railway e ambiente de demonstração](./-TCC-/RAILWAY_TEST.md)
- [Observabilidade, logs e automação de testes](./-TCC-/docs/OBSERVABILIDADE_E_TESTES.md)
- [Estrutura do banco de dados](./-TCC-/database/scripts/criacaotables.sql)
- [Verificações automatizadas no GitHub Actions](https://github.com/silvestrevini11/Zubbo-App/actions)

---

<div align="center">

**Zubbo — Encontre. Pratique. Conecte-se.**

*Um TCC de Desenvolvimento de Sistemas dedicado a aproximar pessoas por meio do esporte.*

</div>
