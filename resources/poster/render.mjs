// HTML → PNG melalui Playwright. Dipanggil oleh PosterService.
// Guna Chrome/Chromium yang sudah ada pada mesin; jangan sesekali muat turun.
import { existsSync } from 'node:fs';
import { chromium } from 'playwright';

const [, , htmlPath, outPath, sizeArg] = process.argv;

if (!htmlPath || !outPath) {
    console.error('Guna: node render.mjs <html> <png> [saiz]');
    process.exit(2);
}

/*
 * Cari browser sendiri.
 *
 * Muat turun Chromium Playwright kerap gagal pada pelayan yang perlahan atau
 * berpagar, dan memasang Chrome dari repo distro adalah jalan yang lebih boleh
 * dipercayai. Daripada memaksa pemilik pelayan menetapkan satu pemboleh ubah
 * dengan betul, kita periksa tempat-tempat biasa dahulu. Satu langkah manual
 * kurang bermakna satu cara kurang untuk tersalah.
 */
const CANDIDATES = [
    process.env.PLAYWRIGHT_CHROMIUM_PATH,
    '/usr/bin/google-chrome-stable',
    '/usr/bin/google-chrome',
    '/opt/google/chrome/chrome',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
    '/snap/bin/chromium',
];

const executablePath = CANDIDATES.find((path) => path && existsSync(path));

let browser;

try {
    browser = await chromium.launch({
        // undefined = guna Chromium terbina Playwright, kalau ia sudah dipasang.
        executablePath,
        args: ['--no-sandbox', '--disable-dev-shm-usage', '--font-render-hinting=none'],
    });
} catch (error) {
    // Beritahu APA yang dicari, supaya sesiapa yang membaca log tahu langkah
    // seterusnya tanpa meneka.
    console.error(
        'Tidak jumpa Chrome atau Chromium. Diperiksa:\n  '
        + CANDIDATES.filter(Boolean).join('\n  ')
        + '\n' + (error?.message ?? '')
    );
    process.exit(3);
}

try {
    const page = await browser.newPage({
        viewport: { width: size(), height: size() },
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

function size() {
    return Number(sizeArg || 1080);
}
