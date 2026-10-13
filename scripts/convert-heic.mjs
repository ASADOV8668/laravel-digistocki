import fs from 'node:fs/promises';
import convert from 'heic-convert';

const [, , sourcePath, targetPath] = process.argv;

if (!sourcePath || !targetPath) {
    throw new Error('Source and target paths are required.');
}

const source = await fs.readFile(sourcePath);
const png = await convert({
    buffer: source,
    format: 'PNG',
    quality: 1,
});

await fs.writeFile(targetPath, png);
