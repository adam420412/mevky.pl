"""Build an installable theme ZIP and SHA-256 manifest, without local runtime."""
from pathlib import Path
import hashlib
import re
import zipfile

root = Path(__file__).resolve().parents[1]
theme = root / 'mevky'
version = re.search(r'^Version:\s*(\S+)', (theme / 'style.css').read_text(), re.M).group(1)
release = root / 'release'
release.mkdir(exist_ok=True)
target = release / f'MEVKY-{version}.zip'
allowed = {'.php', '.css', '.html', '.json', '.js', '.woff2', '.png', '.webp', '.jpg', '.jpeg', '.svg', '.txt', '.md'}
with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
    for path in sorted(theme.rglob('*')):
        if not path.is_file() or path.name.startswith('.') or path.name.endswith('-editorial.png'):
            continue
        if path.suffix not in allowed:
            raise SystemExit(f'Unexpected release file: {path.relative_to(theme)}')
        entry = zipfile.ZipInfo('mevky/' + path.relative_to(theme).as_posix(), date_time=(2026, 1, 1, 0, 0, 0))
        entry.compress_type = zipfile.ZIP_DEFLATED
        entry.external_attr = 0o100644 << 16
        archive.writestr(entry, path.read_bytes())
with zipfile.ZipFile(target) as archive:
    assert archive.testzip() is None
    assert 'mevky/inc/payments.php' in archive.namelist()
    assert len([n for n in archive.namelist() if '/payments/' in n]) == 4
    count = len(archive.namelist())
digest = hashlib.sha256(target.read_bytes()).hexdigest()
target.with_suffix('.sha256').write_text(f'{digest}  {target.name}\n')
print(f'{target}: {count} files, {target.stat().st_size:,} bytes; ZIP integrity OK')

helper_source = root / 'deployment/mevky-deployment-helper/mevky-deployment-helper.php'
helper_zip = release / 'MEVKY-pomocnik-wdrozenia-1.0.0.zip'
with zipfile.ZipFile(helper_zip, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
    archive.write(helper_source, 'mevky-deployment-helper/mevky-deployment-helper.php')
with zipfile.ZipFile(helper_zip) as archive:
    assert archive.testzip() is None

# The outer handoff ZIP contains tools and instructions; only the inner ZIP
# is uploaded through WordPress Appearance > Themes.
handoff = release / f'MEVKY-{version}-wdrozenie.zip'
with zipfile.ZipFile(handoff, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
    for path in [target, helper_zip, target.with_suffix('.sha256'), root/'deployment/READINESS.md', root/'deployment/preflight.php', root/'deployment/import-product-copy.php', root/'local/data/asset-sources.json']:
        name = path.name if path.parent == release else ('deployment/' + path.name if path.parent.name == 'deployment' else 'sources/' + path.name)
        archive.write(path, name)
with zipfile.ZipFile(handoff) as archive:
    assert archive.testzip() is None
print(f'{handoff}: handoff package integrity OK')
