S.A – Senai					 		         Técnico em Desenvolvimento de Sistemas 													Noturno/26

 Problema/Necessidade

Uma empresa de aluguel de ônibus urbano precisava controlar o uso dos ônibus para poder organizar sua agenda e evitar indisponibilidade da sua frota que está localizada em uma garagem remota, os principais pontos levantados são: saber quais ônibus estão disponíveis, local da vaga onde está estacionado, registrar qual motorista está utilizando qual veículo, controle de horários agendados e direcionamento de rotas. Com isso contatou nossa equipe para desenvolver um sistema WEB para monitoramento e IoT para acesso e controle remoto da garagem.

Solução

O local possui vagas para 4 veículos e cada uma terá um armário com a chave respectiva ao veículo, e quando o ônibus retorna para a garagem. O motorista terá um cartão magnético como sua identificação para ter acesso a chave. Com esses dados uma aplicação Web programada em PHP e integrada a um banco de dados usando MySQL que ficara na sede da empresa e os dispositivos ESP32 na garagem rodando via WEB.

________________________________________________________________________________________________________________________________

Requisitos de Negócios 

O sistema deve permitir que a empresa de aluguel de ônibus controle, em tempo real, a disponibilidade da frota, a ocupação das vagas da garagem, a identificação dos motoristas e o histórico de uso dos veículos, reduzindo conflitos de agenda e indisponibilidade não prevista.

1. Gestão de frota

- O sistema deve cadastrar ônibus com placa, modelo, status operacional e vínculo com uma vaga da garagem utilizando a chave como controle primário.

- O sistema deve permitir consultar rapidamente quais ônibus estão disponíveis.

- O sistema deve registrar automaticamente a entrada e a saída de cada chave de ônibus na garagem.

- O sistema deve impedir que o mesmo ônibus seja alocado para duas agendas que se sobreponham.

2. Controle de vagas e chaves

- A garagem deve possuir quatro vagas identificadas, e cada vaga deve estar vinculada a um armário que guarda a chave do ônibus correspondente.

- O sistema deve registrar qual chave está guardada no respectivo armário.

- Quando um motorista retirar uma chave, o sistema deve atualizar o status do ônibus para “em uso” e liberar a vaga como “em uso” ou “aguardando retorno”.

- Quando o motorista devolver a chave, o sistema deve registrar o retorno do ônibus, atualizar a disponibilidade da vaga e encerrar a alocação.

- O sistema deve ter um alerta caso a chave de um ônibus fique indisponível por muito tempo.

3. Identificação e acesso dos motoristas

- Cada motorista deve receber um cartão RFID único, vinculado ao seu cadastro no sistema.

- O sistema deve validar o cartão antes de liberar o acesso à chave do ônibus.

- O sistema deve permitir acesso somente a motoristas autorizados, ativos e habilitados para operar o veículo solicitado.

- Ao aproximar ou passar o cartão, o sistema deve identificar o motorista, o ônibus vinculado ao armário e registrar data, hora e resultado da tentativa de acesso.

- O sistema deve bloquear o acesso caso o cartão esteja inativo, cancelado, expirado ou não autorizado.

- A empresa deve poder cadastrar, editar, suspender e revogar cartões de motoristas.

4. Monitoramento e IoT da garagem

- Cada armário deve possuir um dispositivo ESP32 conectado à rede da garagem e integrado à aplicação web PHP com GET HTTPS.

- O ESP32 deve controlar a abertura do compartimento da chave somente após a validação do cartão magnético do motorista.

- O ESP32 deve enviar à aplicação web informações de acesso, status do armário, abertura de porta e eventos de falha.

- O sistema deve permitir que administradores monitorem remotamente o estado de cada armário: online, offline, travado, destrancado, em falha ou em manutenção.

- O sistema deve permitir o acionamento remoto de abertura de armário apenas por usuários administradores devidamente autorizados.

5. Regras de negócio essenciais

- Um ônibus só pode estar vinculado a uma vaga por vez.

- Uma chave só pode estar com um motorista por vez.

- A devolução da chave deve encerrar ou atualizar automaticamente a alocação correspondente.

- Toda abertura de armário deve estar associada a um cartão, motorista, ônibus e evento registrados no sistema.

________________________________________________________________________________________________________________________________

Requisitos não-funcionais

*Desempenho*

RNF-01 — Tempo de resposta: O sistema web deve responder às operações comuns de consulta e cadastro em até 3 segundos.

RNF-02 — Validação de acesso: A validação do cartão RFID e a liberação do armário devem ocorrer em até 2 segundos após a aproximação do cartão.

RNF-03 — Atualização de status: Alterações de status de chave, ônibus e armário devem refletir no painel administrativo em até 10 segundos após o evento.

RNF-04 — Capacidade inicial: O sistema deve suportar no mínimo 4 ESP32s, 4 vagas/armários, 10 motoristas e 10 ônibus sem degradação perceptível de desempenho.

*Confiabilidade e disponibilidade*

RNF-05 — Disponibilidade do sistema web: O sistema deve ter disponibilidade mínima de 99% durante o horário comercial.

RNF-06 — Monitoramento de dispositivos: O sistema deve detectar e exibir armários offline, em falha ou sem resposta em até 5 minutos após a ocorrência.

*Segurança*

RNF-07— Controle de acesso por perfil: O sistema deve implementar perfis de usuário, como administrador e motorista.

RNF-09— Abertura remota restrita: A abertura remota de armário deve ser permitida exclusivamente a administradores autorizados e deve exigir confirmação ou autenticação adicional.

RNF-10 — Proteção contra cartões inválidos: Cartões inativos, revogados, expirados, duplicados ou não cadastrados devem ser bloqueados e gerar registro de tentativa negada.

RNF-11 — Confidencialidade dos dados: Dados pessoais de motoristas, cartões devem ter acesso restrito conforme o perfil do usuário.

RNF-12 — Rastreabilidade: Cada abertura de armário deve estar associada a cartão, motorista, ônibus, armário, data, hora e resultado da operação.

*Usabilidade*

RNF-13 — Interface intuitiva: O painel web deve permitir localizar ônibus disponíveis, vagas ocupadas, chaves em uso e histórico de acessos em poucos cliques.

RNF-14 — Feedback ao motorista: O ESP32 deve fornecer feedback de forma visual indicando acesso autorizado, negado ou falha.

RNF-15 — Acessibilidade básica: A interface deve ser de fácil compreensão e objetiva nas exibições de informações.

*Compatibilidade*

RNF-16 — Integração ESP32: Os dispositivos ESP32 devem comunicar-se com a aplicação PHP por requisições HTTP/HTTPS padronizadas, preferencialmente REST/JSON.

RNF-17 — Banco de dados: O sistema deve utilizar MySQL como banco de dados relacional, conforme especificado.

*Manutenibilidade*

RNF-18 — Código modular: A aplicação deve separar camadas de interface, regras de negócio, acesso ao banco e integração IoT.

RNF-19 — Testabilidade: As regras críticas — sobreposição de status de chave, devolução e permissões — devem ser testáveis de forma automatizada.

*Portabilidade e implantação*

RNF-20 — Hospedagem local: A aplicação PHP e o MySQL devem poder ser instalados em servidor local na sede da empresa.

RNF-21 — Backup: O banco de dados deve ser copiado automaticamente pelo menos uma vez por dia, com retenção mínima de 30 dias.

*Requisitos IoT específicos*

RNF-22 — Chaves armazenadas com segurança: Credenciais de Wi-Fi, certificados e tokens dos ESP32s não devem ficar expostos em código-fonte público.

RNF-23 — Comandos assinados ou autorizados: Comandos de abertura remota devem conter identificação do administrador e horário.

RNF-24 — Falha segura: Em caso de falha de comunicação, o armário deve permanecer fisicamente seguro e não abrir automaticamente sem validação.

________________________________________________________________________________________________________________________________

Requisitos Funcionais

*Autenticação e usuários*

RF-001 — Login: O sistema deve permitir que administradores e operadores façam login com usuário e senha.

RF-002 — Perfis de acesso: O sistema deve gerenciar perfis de usuário, incluindo, no mínimo, administrador e motorista.

RF-003 — Permissões: O sistema deve restringir funcionalidades conforme o perfil do usuário autenticado.

RF-004 — Cadastro de usuários: O administrador deve poder cadastrar, editar, ativar, suspender e excluir usuários do sistema.

RF-005 — Logout: O sistema deve permitir que o usuário encerre sua sessão manualmente.

*Gestão de motoristas*

RF-006 — Cadastro de motorista: O sistema deve permitir cadastrar motoristas com nome, CPF, telefone, e-mail, status e categoria de habilitação.

RF-007 — Edição de motorista: O sistema deve permitir editar os dados cadastrais e o status de um motorista.

RF-008 — Suspensão de motorista: O sistema deve permitir suspender um motorista, impedindo novas retiradas de chave enquanto estiver suspenso.

RF-009 — Consulta de motoristas: O sistema deve permitir consultar motoristas por nome, CPF ou cartão RFID.

RF-010 — Histórico do motorista: O sistema deve permitir visualizar o histórico de retiradas, devoluções, acessos de um motorista.

*Gestão de cartões RFID*

RF-011 — Cadastro de cartão: O sistema deve permitir cadastrar cartões RFID únicos e vinculá-los a um motorista.

RF-012 — Status do cartão: O sistema deve permitir definir o status do cartão como ativo, inativo, suspenso, expirado ou revogado.

RF-013 — Edição de cartão: O sistema deve permitir editar os dados de um cartão RFID, incluindo vínculo com motorista e data de validade.

RF-014 — Revogação de cartão: O sistema deve permitir revogar um cartão RFID, bloqueando imediatamente novas tentativas de acesso.

RF-015 — Consulta de cartão: O sistema deve permitir consultar cartões por número, motorista vinculado, status ou data de validade.

RF-016 — Bloqueio de cartão inválido: O sistema deve negar acesso quando o cartão não estiver cadastrado, estiver revogado, suspenso, inativo ou expirado.

*Gestão de frota*

RF-017 — Cadastro de ônibus: O sistema deve permitir cadastrar ônibus com placa e modelo.

RF-018 — Edição de ônibus: O sistema deve permitir editar os dados cadastrais e o status de um ônibus.

RF-019 — Status do ônibus: O sistema deve permitir classificar o ônibus como disponível, em uso, em manutenção, indisponível ou aguardando retorno.

RF-020 — Consulta de disponibilidade: O sistema deve permitir consultar rapidamente os ônibus disponíveis, em uso ou indisponíveis.

RF-021 — Vínculo com vaga: O sistema deve permitir associar cada ônibus a apenas uma vaga da garagem por vez.

RF-022 — Histórico do ônibus: O sistema deve permitir visualizar o histórico de uso, alocações, acessos e devoluções de cada ônibus.

*Gestão de vagas e armários*

RF-023 — Cadastro de vaga: O sistema deve permitir cadastrar as quatro vagas da garagem, com identificação e status.

RF-024 — Cadastro de armário: O sistema deve permitir cadastrar um armário para cada vaga, vinculando-o ao ônibus e à chave correspondente.

RF-025 — Vínculo chave-armário: O sistema deve registrar qual chave está guardada em qual armário.

RF-026 — Status do armário: O sistema deve registrar e exibir o status de cada armário como online, offline, travado, destrancado, em falha ou em manutenção.

RF-027 — Atualização automática: O sistema deve atualizar automaticamente o status da vaga quando a chave for retirada ou devolvida.

*Controle de chaves*

RF-028 — Registro de retirada: Quando um motorista autorizado retirar uma chave, o sistema deve registrar data, hora, motorista, cartão, ônibus e armário sendo disparada essa informação através do sensor da chave.

RF-029 — Registro de devolução: Quando o motorista devolver a chave, o sistema deve registrar a devolução e atualizar o status do ônibus, da chave, da vaga e do armário.

RF-030 — Controle de posse: O sistema deve garantir que uma chave esteja associada a apenas um motorista por vez.

RF-031 — Encerramento de alocação: A devolução da chave deve encerrar ou atualizar automaticamente a alocação correspondente.

RF-032 — Alerta de chave retida: O sistema deve gerar alerta quando uma chave permanecer fora do armário por tempo superior ao limite configurado.

*Acesso via RFID e IoT*

RF-033 — Leitura do cartão: Cada ESP32 deve ler o cartão RFID aproximado pelo motorista e enviar o identificador ao sistema web.

RF-034 — Validação de acesso: O sistema deve validar o cartão, o motorista vinculado, o status do cartão e a permissão para operar o ônibus do armário.

RF-035 — Liberação do armário: Após validação bem-sucedida, o sistema deve autorizar o ESP32 a destrancar o compartimento da chave.

RF-036 — Registro de acesso autorizado: O sistema deve registrar toda abertura autorizada com cartão, motorista, ônibus, armário, data, hora e resultado.

RF-037 — Feedback no dispositivo: O ESP32 deve informar ao motorista pelo display, se o acesso foi autorizado ou negado.

RF-038 — Comando de travamento: O sistema deve permitir que o ESP32 trave novamente o armário após a retirada ou devolução da chave.

*Monitoramento e painel*

RF-039 — Painel inicial: O sistema deve exibir um painel com ônibus disponíveis, ônibus em uso, vagas ocupadas, chaves retiradas, armários online/offline e alertas ativos.

RF-040 — Monitoramento em tempo real: O sistema deve permitir que administradores acompanhem o estado atual de cada armário e vaga.

RF-041 — Notificações: O sistema deve gerar alertas para chave retida por muito tempo, armário offline, falha de dispositivo, acesso negado ou conflito de agenda.

RF-042 — Relatório de uso: O sistema deve gerar relatórios de uso dos ônibus, incluindo retiradas, devoluções, tempo de utilização e motorista responsável.

*Automatização*

RF-043 — Exclusividade de vaga: O sistema deve impedir que dois ônibus estejam vinculados à mesma vaga simultaneamente.

RF-044 — Exclusividade de chave: O sistema deve impedir que uma mesma chave seja vinculada a dois motoristas ao mesmo tempo.

RF-045 — Consistência de status: Ao retirar a chave, o sistema deve alterar automaticamente o ônibus para “em uso”, a vaga para “em uso” ou “aguardando retorno” e o armário para “destrancado”.

RF-046 — Retorno do ônibus: Ao devolver a chave, o sistema deve alterar o ônibus para “disponível” ou “aguardando inspeção”, a vaga para “ocupada” e o armário para “travado”.

RF-047 — Rastreabilidade completa: Toda abertura de armário deve estar associada a um cartão, motorista, ônibus registrado no sistema.

RF-048 — Prevenção de duplicidade: O sistema deve impedir o cadastro de placas, cartões RFID e armários duplicados.

________________________________________________________________________________________________________________________________



| **BMC** | **Conteúdo** |
| --- | --- |
| **Parceiros-chave** | Fornecedor de ESP32, leitores RFID, fechaduras eletrônicas e armários; empresa de internet/provedor de rede na garagem; hospedagem ou provedor de servidor; desenvolvedores e suporte técnico; fabricantes ou locadoras de ônibus; empresa de segurança eletrônica para instalação. |
| **Atividades-chave** | Desenvolvimento e manutenção do sistema web em PHP/MySQL; programação e integração dos ESP32s; instalação e configuração dos armários; cadastro de frota, motoristas, cartões e agendas; monitoramento de dispositivos; suporte técnico; atualizações de segurança; treinamento de administradores. |
| **Recursos-chave** | Plataforma web PHP/MySQL; banco de dados de frota, motoristas, chaves e acessos; dispositivos ESP32, leitores RFID, fechaduras e armários; rede Wi-Fi ou Ethernet na garagem; equipe de desenvolvimento e suporte; servidor local ou em nuvem; propriedade intelectual do software e documentação técnica. |
| **Proposta de valor** | Controle em tempo real da disponibilidade dos ônibus, das vagas e das chaves; redução de conflitos de agenda e indisponibilidade não prevista; rastreabilidade de quem retirou cada chave; acesso seguro por cartão RFID; monitoramento remoto da garagem; alertas de chaves retidas e falhas nos armários; histórico completo para auditoria e gestão. |
| **Relacionamento com clientes** | Atendimento consultivo na implantação; treinamento dos administradores e operadores; suporte técnico por WhatsApp, e-mail ou telefone; acompanhamento periódico do sistema; canal para solicitações de melhorias; contrato de manutenção e suporte contínuo. |
| **Canais** | Venda direta B2B para empresas de aluguel de ônibus; indicações e parcerias com locadoras e oficinas; site institucional e demonstração do sistema; Google e redes sociais; visita técnica para diagnóstico da garagem; proposta comercial e apresentação demonstrativa. |
| **Segmentos de clientes** | Empresas de aluguel de ônibus urbano; locadoras de veículos de grande porte; empresas de transporte executivo; frotistas com garagem remota; cooperativas ou transportadoras; empresas que precisam controlar chaves, motoristas e agenda de veículos. |
| **Estrutura de custos** | Desenvolvimento e manutenção do software; compra de ESP32, RFID, fechaduras, armários e cabeamento; instalação e configuração na garagem; servidor, domínio, certificado SSL e backup; internet e energia na garagem; suporte técnico; marketing e vendas; treinamento; custos administrativos. |
| **Fontes de receita** | Implantação inicial: sistema web, hardware, instalação e configuração; mensalidade de licença/SaaS; assinatura de suporte e manutenção; cobrança por dispositivo ESP32/armário adicional; atualizações e novas funcionalidades; consultoria para expansão da garagem ou integrações; venda de cartões RFID e peças de reposição. |

