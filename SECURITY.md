# Política de seguridad

## Reportar una vulnerabilidad

No abras un issue público. Escribí a **ventas@moldpack.com.ar** con el asunto `SEGURIDAD` e incluí los pasos para reproducir el problema. Respondemos dentro de los 5 días hábiles.

## Controles implementados (V1)

| Área | Control | Test de regresión |
|---|---|---|
| Formularios públicos | CSRF, honeypot, time-trap tolerante, validación server-side con límites de tamaño | `ContactFormSecurityTest`, `NewsletterSecurityTest`, `CsrfProtectionTest` |
| Rate limiting | Limitadores con nombre y clave propia por endpoint (`contact`, `newsletter`, `client-login`, `client-register`, `site-search`, `admin-login`); un usuario limitado se recupera al vencer la ventana | `*_rate_limit_*`, `test_other_endpoints_do_not_consume_the_contact_quota` |
| Relay de correo | Máximo 3 confirmaciones por dirección de destino por hora; nombre, teléfono y empresa rechazan CR/LF | `test_confirmation_mail_cannot_be_used_to_flood_one_address`, `test_header_injection_in_name_is_rejected` |
| Sesiones | `HttpOnly`, `SameSite=Strict`, sesión cifrada, regeneración en login e invalidación en logout | `SessionSecurityTest` |
| Credenciales | Rotación del remember token al cambiar contraseña; `auth.session` en el admin | `test_remember_token_*`, `test_admin_session_is_invalidated_*` |
| Autorización | Middleware `admin` y `client`, ownership en facturas y registro sin campos privilegiados | `AccessControlAndInputTest` |
| Uploads | MIME real (finfo), nombres aleatorios generados por el servidor, rechazo de SVG activo | `test_malicious_svg_*`, `test_executable_and_spoofed_uploads_*` |
| Exportaciones | Neutralización de fórmulas CSV | `test_csv_export_neutralizes_formulas` |
| Cabeceras | CSP estricta en el admin; nosniff, X-Frame-Options, Referrer-Policy, Permissions-Policy y HSTS en el sitio público | `test_public_and_admin_responses_send_security_headers` |
| Dependencias | `composer audit` y `npm audit` sin advisories | — |

## Riesgos residuales conocidos

- **Contraseñas de clientes recuperables por el admin** (`password_encrypted`): es una decisión de negocio del cliente. Mitigación: la columna nunca sale del backend (`$hidden`) y verla exige la contraseña del administrador.
- Los comprobantes de pago y adjuntos de pedidos se guardan en el disco `public` con nombres aleatorios de 40 caracteres. Se recomienda migrarlos a un disco privado.
- El HTML del CMS se imprime sin escapar. Solo pueden editarlo administradores.
- La newsletter no tiene doble opt-in.
- La configuración del servidor (HTTPS, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, bloqueo de PHP en `public/storage`) debe verificarse en producción.
