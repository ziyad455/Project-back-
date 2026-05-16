/**
 * Helper script to copy a file from the backend workspace to the frontend.
 * Usage: node _write_front.js <relative-path-from-Project-Front/src> <source-file>
 */
const fs = require('fs');
const path = require('path');

const FRONT_SRC = path.resolve(__dirname, '..', 'Project-Front', 'src');
const relPath = process.argv[2];
const srcFile = process.argv[3];
if (!relPath || !srcFile) { console.error('Usage: node _write_front.js <rel-path> <src-file>'); process.exit(1); }

const target = path.join(FRONT_SRC, relPath);
const content = fs.readFileSync(srcFile, 'utf-8');
fs.writeFileSync(target, content, 'utf-8');
console.log('Wrote', target);
