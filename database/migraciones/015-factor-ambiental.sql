-- Parte 18 de las extras (19/10/2026): impacto ambiental.
-- Cada tipo de residuo puede llevar su factor de emision evitada: kg de CO2e que se evitan por cada kg que se
-- RECICLA en lugar de enterrarse, con su fuente. Los factores salen del modelo WARM de la EPA de Estados Unidos
-- (version 16, diciembre de 2023): neto de reciclar menos neto de enterrar, en MTCO2E por tonelada corta,
-- pasado a kg de CO2e por kg (1 tonelada corta = 907,18474 kg; 1 MTCO2E = 1000 kg). Son valores de referencia de
-- otro pais: se muestran siempre como "estimacion" con su fuente. Si un tipo no tiene fuente verificada, queda
-- en NULL y el sistema muestra solo kg para ese tipo (no se inventa nada).

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tipo_residuo' AND COLUMN_NAME = 'factor_co2_kg_por_kg');
SET @sql = IF(@existe = 0,
              'ALTER TABLE tipo_residuo ADD COLUMN factor_co2_kg_por_kg DECIMAL(6,3) NULL, ADD COLUMN fuente_factor VARCHAR(255) NULL, ADD COLUMN url_fuente VARCHAR(200) NULL',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

UPDATE tipo_residuo SET factor_co2_kg_por_kg = 1.047,
    fuente_factor = 'EPA WARM v16 (dic. 2023), Exhibit 5-3, Mixed Plastics: reciclar -0.93 y enterrar +0.02 MTCO2E por tonelada corta (0.95 evitadas = 1.047 kg CO2e por kg)',
    url_fuente = 'https://www.epa.gov/system/files/documents/2023-12/warm_containers_packaging_and_non-durable_goods_materials_v16_dec.pdf'
WHERE nombre = 'Plastico' AND factor_co2_kg_por_kg IS NULL;
UPDATE tipo_residuo SET factor_co2_kg_por_kg = 0.331,
    fuente_factor = 'EPA WARM v16 (dic. 2023), Exhibit 1-4, Glass: reciclar -0.28 y enterrar +0.02 MTCO2E por tonelada corta (0.30 evitadas = 0.331 kg CO2e por kg)',
    url_fuente = 'https://www.epa.gov/system/files/documents/2023-12/warm_containers_packaging_and_non-durable_goods_materials_v16_dec.pdf'
WHERE nombre = 'Vidrio' AND factor_co2_kg_por_kg IS NULL;
UPDATE tipo_residuo SET factor_co2_kg_por_kg = 3.990,
    fuente_factor = 'EPA WARM v16 (dic. 2023), Exhibit 3-7, Mixed Paper (general): reciclar -3.55 y enterrar +0.07 MTCO2E por tonelada corta (3.62 evitadas = 3.990 kg CO2e por kg)',
    url_fuente = 'https://www.epa.gov/system/files/documents/2023-12/warm_containers_packaging_and_non-durable_goods_materials_v16_dec.pdf'
WHERE nombre = 'Papel' AND factor_co2_kg_por_kg IS NULL;
UPDATE tipo_residuo SET factor_co2_kg_por_kg = 4.861,
    fuente_factor = 'EPA WARM v16 (dic. 2023), Exhibit 2-7, Mixed Metals (35 % latas de aluminio y 65 % de acero): reciclar -4.39 y enterrar +0.02 MTCO2E por tonelada corta (4.41 evitadas = 4.861 kg CO2e por kg)',
    url_fuente = 'https://www.epa.gov/system/files/documents/2023-12/warm_containers_packaging_and_non-durable_goods_materials_v16_dec.pdf'
WHERE nombre = 'Metal' AND factor_co2_kg_por_kg IS NULL;
