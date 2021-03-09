```
    php:
        image: registry.gitlab.com/opencontent/opensegnalazioni/php:latest
        ...
        environment:
        #### Impostazioni interfaccia di login
            EZINI_app__LoginTemplate__Layout: 'row'
            EZINI_app__LoginTemplate__LoginModules__0: 'button|sirac'
            EZINI_app__LoginTemplate__LoginModules__1: 'default'
            EZINI_app__LoginTemplate_default__Title: 'Hai un account sensor?'
            EZINI_app__LoginTemplate_default__Text: "Se possiedi un account sensor abilitato puoi eseguire qui l'accesso"
            EZINI_app__LoginTemplate_default__ButtonText: 'Accedi'
            EZINI_app__LoginTemplate_sirac__Title: 'Vuoi accedere con SPID?'
            EZINI_app__LoginTemplate_sirac__Text: "<p>Questo applicativo utilizza il Sistema Pubblico di Identità Digitale SPID per accedere ai servizi on line.</p>"
            EZINI_app__LoginTemplate_sirac__LinkHref: '/sirac/auth'
            EZINI_app__LoginTemplate_sirac__LinkText: 'Accedi con SPID'

            #### Impostazioni Sirac auth
            EZINI_site__SiteSettings__AllowedRedirectHosts__0: 'login.opensegnalazioni.localtest.me'
            EZINI_site__UserSettings__LogoutRedirect: '/sirac/logout'
            EZINI_site__ExtensionSettings__ActiveExtensions__0: 'ocsiracauth'
            EZINI_ocsiracauth__HandlerSettings__UserHandler: 'OCOpenLoginAuthUserHandler'
            EZINI_ocsiracauth__HandlerSettings__LogoutPath: 'http://login.opensegnalazioni.localtest.me/slo?redirect=https://opensegnalazioni.localtest.me/?logout'
            EZINI_ocsiracauth__HandlerSettings__ServerVariables__0: 'HTTP_X_FORWARDED_USER_NAME'
            EZINI_ocsiracauth__HandlerSettings__ServerVariables__1: 'HTTP_X_FORWARDED_USER_FAMILYNAME'
            EZINI_ocsiracauth__HandlerSettings__ServerVariables__2: 'HTTP_X_FORWARDED_USER_FISCALNUMBER'
            EZINI_ocsiracauth__HandlerSettings__ServerVariables__3: 'HTTP_X_FORWARDED_USER_EMAIL'
            EZINI_ocsiracauth__HandlerSettings__ServerVariables__4: 'HTTP_X_FORWARDED_USER_ADDRESS'
            EZINI_ocsiracauth__HandlerSettings__ServerVariables__5: 'HTTP_X_FORWARDED_USER_MOBILEPHONE'
            EZINI_ocsiracauth__HandlerSettings__ExistingUserHandlers__0: 'OCSiracAuthUserTools::getUserByFiscalCode'
            EZINI_ocsiracauth__HandlerSettings__ExistingUserHandlers__1: 'OCSiracAuthUserTools::getUserByEmail'
            EZINI_ocsiracauth__Mapper__UserLogin: 'HTTP_X_FORWARDED_USER_FISCALNUMBER'
            EZINI_ocsiracauth__Mapper__UserEmail: 'HTTP_X_FORWARDED_USER_EMAIL'
            EZINI_ocsiracauth__Mapper__FiscalCode: 'HTTP_X_FORWARDED_USER_FISCALNUMBER'
            EZINI_ocsiracauth__Mapper__Attributes__first_name: 'HTTP_X_FORWARDED_USER_NAME'
            EZINI_ocsiracauth__Mapper__Attributes__last_name: 'HTTP_X_FORWARDED_USER_FAMILYNAME'
            EZINI_ocsiracauth__Mapper__Attributes__fiscal_code: 'HTTP_X_FORWARDED_USER_FISCALNUMBER'
            
    nginx:
        image: registry.gitlab.com/opencontent/opensegnalazioni/nginx:latest
        ...
        labels:
            traefik.enable: 'true'
            traefik.http.services.opensegnalazioni.loadbalancer.server.port: 80
            traefik.http.routers.opensegnalazioni-https.rule: Host(`opensegnalazioni.localtest.me`)
            traefik.http.routers.opensegnalazioni-https.entrypoints: websecure
            traefik.http.routers.opensegnalazioni-https.tls: null
            traefik.http.routers.opensegnalazioni-https.middlewares: goodheaders
            traefik.http.routers.opensegnalazioni-http.rule: Host(`opensegnalazioni.localtest.me`)
            traefik.http.routers.opensegnalazioni-http.entrypoints: web

            traefik.http.routers.hereami-https.rule: "Host(`opensegnalazioni.localtest.me`) && Path(`/sirac/auth`)"
            traefik.http.routers.hereami-https.entrypoints: websecure
            traefik.http.routers.hereami-https.tls: null
            traefik.http.routers.hereami-https.middlewares: goodheaders, spid-auth
            traefik.http.routers.hereami-https_root.rule: Host(`opensegnalazioni.localtest.me`)
            traefik.http.routers.hereami-https_root.entrypoints: web
            traefik.http.routers.hereami-http.rule: "Host(`opensegnalazioni.localtest.me`) && Path(`/sirac/auth`)"
            traefik.http.routers.hereami-http.entrypoints: web
            traefik.http.routers.hereami-http.middlewares: spid-auth
            traefik.http.routers.hereami-http_root.rule: Host(`opensegnalazioni.localtest.me`)
            traefik.http.routers.hereami-http_root.entrypoints: web
            
    traefik:
        image: 'traefik:2.0'
        ...
        labels:
            ...
            traefik.http.middlewares.spid-auth.forwardauth.address: http://login.opensegnalazioni.localtest.me
            traefik.http.middlewares.spid-auth.forwardauth.authResponseHeaders: "X-Forwarded-User,X-Forwarded-User-Provider,X-Forwarded-User-registeredOffice,X-Forwarded-User-spidCode,X-Forwarded-User-name,X-Forwarded-User-companyName,X-Forwarded-User-ivaCode,X-Forwarded-User-countyOfBirth,X-Forwarded-User-idCard,X-Forwarded-User-gender,X-Forwarded-User-dateOfBirth,X-Forwarded-User-placeOfBirth,X-Forwarded-User-familyName,X-Forwarded-User-fiscalNumber,X-Forwarded-User-digitalAddress,X-Forwarded-User-mobilePhone,X-Forwarded-User-expirationDate,X-Forwarded-User-email,X-Forwarded-User-address,X-Forwarded-User-Session,X-Forwarded-User-Spid-Level,X-Forwarded-User-Spid-Idp"
            traefik.http.middlewares.spid-auth.forwardauth.trustForwardHeader: true
            
    
    auth:
        image: registry.gitlab.com/opencontent/traefik-spid-auth:latest
        labels:
            traefik.enable: 'true'
            traefik.http.services.opensegnalazioni-login.loadbalancer.server.port: 2015
            traefik.http.routers.opensegnalazioni-login-https.rule: Host(`login.opensegnalazioni.localtest.me`)
            traefik.http.routers.opensegnalazioni-login-https.entrypoints: websecure
            traefik.http.routers.opensegnalazioni-login-https.tls: null
            traefik.http.routers.opensegnalazioni-login-https.middlewares: goodheaders
            traefik.http.routers.opensegnalazioni-login-http.rule: Host(`login.opensegnalazioni.localtest.me`)
            traefik.http.routers.opensegnalazioni-login-http.entrypoints: web
        environment:
            SYMFONY_ENV: prod
            ENABLE_PROVIDERS: spid
            COOKIE_LIFETIME_SECONDS: 20
            SPID_LEVEL: 2
            SP_ENTITYID: 'http://login.opensegnalazioni.localtest.me'
            SP_SINGLELOGOUT: 'http://login.opensegnalazioni.localtest.me/slo'
            SP_ACS: 'http://login.opensegnalazioni.localtest.me/acs'
            SP_ORG_NAME: "Comune"
            SP_ORG_DISPLAY_NAME: 'openlogin-test'
            SP_CONTACT_IPA_CODE: 'A_TEST'
            SP_CONTACT_EMAIL: 'info@example.com'
            SP_CONTACT_PHONE: '003912345789'
            TEST_SPID_IDP: 'enabled'
            TEST_SPID_IDP_METADATA_NUMBER: 10
            TEST_SPID_IDP_ID: 'http://spid.localtest.me/'
            TEST_SPID_IDP_NAME: 'Test IdP'
            #facebook
            OAUTH_FACEBOOK_CLIENT_ID: ''
            OAUTH_FACEBOOK_CLIENT_SECRET: ''
            #github
            OAUTH_GITHUB_CLIENT_ID: ''
            OAUTH_GITHUB_CLIENT_SECRET: ''
            #google
            OAUTH_GOOGLE_CLIENT_ID: ''
            OAUTH_GOOGLE_CLIENT_SECRET: ''
            #instagram
            OAUTH_INSTAGRAM_CLIENT_ID: ''
            OAUTH_INSTAGRAM_CLIENT_SECRET: ''
```