// HTML → PNG melalui Playwright. Dipanggil oleh PosterService.
// Guna Chromium yang sudah dipasang; jangan sesekali muat turun sendiri.
import { chromium } from 'playwright';

const [, , htmlPath, outPath, sizeArg] = process.argv;

if (!htmlPath || !outPath) {
    console.error('Guna: node render.mjs <html> <png> [saiz]');
    process.exit(2);
}

const size = Number(sizeArg || 1080);

const browser = await chromium.launch({
    executablePath: process.env.PLAYWRIGHT_CHROMIUM_PATH || undefined,
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--font-render-hinting=none'],
});

try {
    const page = await browser.newPage({
        viewport: { width: size, height: size },
        deviceScaleFactor: 1,
    });

    await page.goto('file://' + htmlPath, { waitUntil: 'load' });
    await page.evaluate(() => document.fonts.ready);
    // Auto-fit menulis semula saiz font selepas layout; beri ia satu frame.
    await page.waitForTimeout(120);

    await page.screenshot({ path: outPath, type: 'png' });
} finally {
    await browser.close();
}
