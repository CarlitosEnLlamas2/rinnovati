-- ============================================
-- RINNOVATI — Pegar en phpMyAdmin (pestaña SQL)
-- con la base de datos ya seleccionada
-- ============================================

CREATE TABLE IF NOT EXISTS registros (
  id                        INT AUTO_INCREMENT PRIMARY KEY,

  order_id                  VARCHAR(60)   NOT NULL UNIQUE,
  moneda                    VARCHAR(5)    NOT NULL DEFAULT 'COP',
  monto                     INT           NOT NULL,
  estado                    ENUM('pendiente','aprobado','rechazado','error') NOT NULL DEFAULT 'pendiente',
  razon_rechazo             VARCHAR(255)  DEFAULT NULL,
  bold_tx_id                VARCHAR(100)  DEFAULT NULL,

  nombre                    VARCHAR(120)  NOT NULL,
  email                     VARCHAR(120)  NOT NULL,
  telefono                  VARCHAR(30)   DEFAULT NULL,
  cedula                    VARCHAR(30)   DEFAULT NULL,
  pais                      VARCHAR(60)   DEFAULT NULL,
  ciudad                    VARCHAR(80)   DEFAULT NULL,

  especialidad              VARCHAR(100)  DEFAULT NULL,
  registro_medico           VARCHAR(60)   DEFAULT NULL,
  institucion               VARCHAR(150)  DEFAULT NULL,
  fecha_sesion              VARCHAR(50)   NOT NULL,
  fuente                    VARCHAR(80)   DEFAULT NULL,

  firma                     VARCHAR(120)  DEFAULT NULL,
  fecha_firma               VARCHAR(60)   DEFAULT NULL,
  cert_medico_titulado      TINYINT(1)    NOT NULL DEFAULT 0,
  cert_habilitacion_vigente TINYINT(1)    NOT NULL DEFAULT 0,
  cert_datos_veridicos      TINYINT(1)    NOT NULL DEFAULT 0,
  cert_acepta_terminos      TINYINT(1)    NOT NULL DEFAULT 0,
  cert_timestamp            DATETIME      DEFAULT NULL,

  created_at                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_email   (email),
  INDEX idx_estado  (estado),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
