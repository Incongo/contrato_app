// scripts/bdns_puppeteer.js
// Script de Puppeteer para descargar JSON de BDNS

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

(async () => {
    console.log('🚀 Iniciando navegador...');
    const browser = await puppeteer.launch({ 
        headless: false,
        defaultViewport: { width: 1280, height: 800 }
    });
    
    const page = await browser.newPage();
    
    console.log('📡 Navegando a BDNS...');
    await page.goto('https://www.pap.hacienda.gob.es/bdnstrans/GE/es/convocatorias', {
        waitUntil: 'networkidle2'
    });
    
    console.log('✅ Página cargada');
    
    // Hacer clic en el enlace de Convocatorias
    await page.evaluate(() => {
        const links = Array.from(document.querySelectorAll('a'));
        const convocatoriasLink = links.find(l => l.innerText.includes('Convocatorias'));
        if (convocatoriasLink) convocatoriasLink.click();
    });
    
    await new Promise(resolve => setTimeout(resolve, 3000));
    
    // Cambiar a 1000 registros por página
    await page.evaluate(() => {
        const select = document.querySelector('select[aria-label*="elementos"], select');
        if (select) {
            select.value = '1000';
            select.dispatchEvent(new Event('change'));
        }
    });
    
    await new Promise(resolve => setTimeout(resolve, 3000));
    
    // Hacer clic en el botón JSON
    console.log('🔍 Buscando botón JSON...');
    
    // Configurar descarga en la carpeta actual
    const client = await page.target().createCDPSession();
    await client.send('Browser.setDownloadBehavior', {
        behavior: 'allow',
        downloadPath: path.join(__dirname, '../storage/temp')
    });
    
    await page.evaluate(() => {
        const jsonButton = Array.from(document.querySelectorAll('a, button')).find(
            el => el.innerText === 'JSON'
        );
        if (jsonButton) {
            console.log('✅ Botón JSON encontrado');
            jsonButton.click();
        } else {
            console.log('❌ Botón JSON no encontrado');
        }
    });
    
    // Esperar descarga
    console.log('⏳ Esperando descarga...');
    await new Promise(resolve => setTimeout(resolve, 10000));
    
    console.log('✅ Proceso completado');
    await browser.close();
})();