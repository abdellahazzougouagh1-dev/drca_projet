import zipfile
import os
import re

docx_path = r'storage\app\templates\bordereau.docx'
temp_dir = r'storage\app\templates\temp_border_clean'

if not os.path.exists(docx_path):
    print("Fichier non trouvé")
    exit()

with zipfile.ZipFile(docx_path, 'r') as zip_ref:
    zip_ref.extractall(temp_dir)

doc_xml_path = os.path.join(temp_dir, 'word', 'document.xml')
with open(doc_xml_path, 'r', encoding='utf-8') as f:
    xml = f.read()

# Strip any XML tags inside ${...}
def clean_var(match):
    return re.sub(r'<[^>]+>', '', match.group(0))

xml = re.sub(r'\$\{.*?\}', clean_var, xml)

with open(doc_xml_path, 'w', encoding='utf-8') as f:
    f.write(xml)

new_docx_path = r'storage\app\templates\bordereau.docx'
with zipfile.ZipFile(new_docx_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for root, dirs, files in os.walk(temp_dir):
        for file in files:
            file_path = os.path.join(root, file)
            arcname = os.path.relpath(file_path, temp_dir)
            zipf.write(file_path, arcname)

print("Bordereau cleaned")
