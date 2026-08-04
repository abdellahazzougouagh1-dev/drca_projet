from pathlib import Path
p = Path(r'C:\xampp\php\php.ini')
t = p.read_text(encoding='utf-8')
old = ';extension=gd'
new = 'extension=gd'
if old in t:
    t = t.replace(old, new, 1)
else:
    t = t.replace('extension=fileinfo\n', 'extension=fileinfo\n' + new + '\n')
p.write_text(t, encoding='utf-8')
print('GD_ENABLED')
