import xml.etree.ElementTree as ET
tree = ET.parse('storage/app/templates/temp_est/word/document.xml')
root = tree.getroot()
with open('est_text.txt', 'w', encoding='utf-8') as f:
    f.write(''.join(root.itertext()))
