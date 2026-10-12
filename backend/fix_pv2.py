import zipfile
import os
import re
import shutil

docx_path = r'storage\app\templates\pv_ouverture.docx'
temp_dir = r'storage\app\templates\temp_pv3'

if os.path.exists(temp_dir):
    shutil.rmtree(temp_dir)

with zipfile.ZipFile(docx_path, 'r') as zip_ref:
    zip_ref.extractall(temp_dir)

doc_xml_path = os.path.join(temp_dir, 'word', 'document.xml')
with open(doc_xml_path, 'r', encoding='utf-8') as f:
    xml = f.read()

# We need to replace avis dachat
xml = xml.replace('avis dachat', '${ref_lettre}')
# We need to replace Kénitra in 'Fait à Kénitra le'
xml = xml.replace('Fait à Kénitra', 'Fait à ${lieu_signature}')

# Now for the signatures at the bottom!
# The signatures block at the bottom contains:
# AKABBABI ABDELLATIF, TAOUIL Hasnaa, OULD ABBOUIBTISSAM (wait, in pv_text it was OULD ABBOUIBTISSAM or OULD ABBOU IBTISSAM)
# Let's check pv_text.txt: "AKABBABI ABDELLATIFTAOUIL HasnaaOULD ABBOUIBTISSAM"
# In xml it's probably split. Let's replace them globally.
# But wait, we already did some replacements in the tables in fix_pv.py!
# This means the current `pv_ouverture.docx` has ALREADY been processed by fix_pv.py.
# So `AKABBABI ABDELLATIF` in the table is ALREADY `${commission_nom}`.
# And `TAOUIL Hasnaa` in the table is ALREADY deleted!
# So any remaining `AKABBABI ABDELLATIF` must be the signature!
# Let's check if the remaining names exist and replace them.

xml = xml.replace('AKABBABI ABDELLATIF', '${sign_president}')
xml = xml.replace('TAOUIL Hasnaa', '${sign_membre1}')
xml = xml.replace('OULD ABBOU IBTISSAM', '${sign_membre2}')
xml = xml.replace('OULD ABBOUIBTISSAM', '${sign_membre2}') # Handle missing space

# Strip any XML tags inside the NEW ${...}
def clean_var(match):
    return re.sub(r'<[^>]+>', '', match.group(0))

xml = re.sub(r'\$\{.*?\}', clean_var, xml)

with open(doc_xml_path, 'w', encoding='utf-8') as f:
    f.write(xml)

new_docx_path = r'storage\app\templates\pv_ouverture.docx'
with zipfile.ZipFile(new_docx_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for root, dirs, files in os.walk(temp_dir):
        for file in files:
            file_path = os.path.join(root, file)
            arcname = os.path.relpath(file_path, temp_dir)
            zipf.write(file_path, arcname)

print("PV updated with more variables")
