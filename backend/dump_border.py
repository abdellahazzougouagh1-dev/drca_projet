import xml.etree.ElementTree as ET
tree = ET.parse('storage/app/templates/temp_border/word/document.xml')
root = tree.getroot()
with open('border_text.txt', 'w', encoding='utf-8') as f:
    f.write(''.join(root.itertext()))
