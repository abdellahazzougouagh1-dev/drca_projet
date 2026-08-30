import xml.etree.ElementTree as ET
tree = ET.parse(r'storage\app\templates\temp_ar\word\document.xml')
root = tree.getroot()
with open('ar_text.txt', 'w', encoding='utf-8') as f:
    f.write(''.join(root.itertext()))
