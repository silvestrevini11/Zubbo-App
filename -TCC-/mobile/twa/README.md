# APK Android do Zubbo (TWA)

Este diretório contém o projeto Android gerado com Bubblewrap 1.27.0. O identificador do aplicativo é `br.com.zubbo.app`. A versão 1 aponta para `https://zubbo-app-demo.up.railway.app/` e abre `/public/index.php`.

## Antes de distribuir

1. Publique o site com `manifest.php`, `sw.js`, ícones, página offline e `/.well-known/assetlinks.json` na mesma origem HTTPS usada em `android/twa-manifest.json`.
2. Confira que `/.well-known/assetlinks.json` responde 200 com `package_name` igual a `br.com.zubbo.app` e a impressão SHA-256 da chave que assina o APK. Sem essa associação, o Android pode abrir a página com barra de endereço. Se trocar a chave, atualize a impressão em `public/assetlinks.php`.
3. Teste em um aparelho real login, mapa, localização, eventos, conversas, foto, Voltar, notificações e modo offline. Um navegador atualizado com suporte a TWA é necessário.
4. Confirme que a hospedagem pode continuar gratuita. O Railway observado em 26/10/2026 estava no plano **Trial**, com crédito e prazo limitados. Não distribua a versão Railway como solução permanente de custo zero sem resolver a hospedagem.

## Chave de assinatura

A chave `twa/.local/zubbo-release.keystore` e a senha em `twa/.local/signing-password.txt` são locais e ignoradas pelo Git. Faça um backup protegido de ambos fora do repositório. A chave e o identificador precisam ser os mesmos para uma versão nova instalar sobre a anterior. Nunca publique a senha ou a chave no Git. A impressão SHA-256, usada em `assetlinks.json`, pode ser divulgada.

## Compilação

Abra `android/` no Android Studio e compile o projeto com JDK 17 ou 21, Android SDK com plataforma 36 e Build Tools. O projeto usa Gradle 8.11.1 e requer Android 7.0 (API 24) ou superior. No PowerShell, defina `JAVA_HOME` e `ANDROID_HOME` e execute `./build-signed-apk.ps1` neste diretório. O script compila, alinha, assina e verifica o APK; a chave e a senha são lidas de `.local/`.

O `twa-manifest.json` é a fonte da configuração do Bubblewrap. Se alterar URL, ícone, permissões ou versão, regenere o projeto Android a partir dele e verifique as mudanças geradas antes de compilar. Para instalar sobre a versão anterior, incremente `appVersionCode` e mantenha o mesmo `packageId` e a mesma chave. Se o domínio mudar, ajuste `host`, `iconUrl`, `webManifestUrl` e `fullScopeUrl`, regenere o projeto, publique o `assetlinks.json` na nova origem e distribua outro APK.

Alterações PHP, CSS e JavaScript são publicadas no servidor e chegam ao aplicativo na próxima navegação ou recarga; elas não exigem novo APK.
