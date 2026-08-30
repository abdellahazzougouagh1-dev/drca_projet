import zipfile
import re

def extract_text(docx_path, out_path):
    try:
        with zipfile.ZipFile(docx_path, 'r') as zip_ref:
            xml = zip_ref.read('word/document.xml').decode('utf-8')
            text = re.sub(r'<[^>]+>', '', xml)
            with open(out_path, 'w', encoding='utf-8') as f:
                f.write(text)
    except Exception as e:
        print(f"Error: {e}")

extract_text(r'storage\app\templates\rapport_presentation.docx', 'rp_text.txt')
extract_text(r'storage\app\templates\resultats_ao.docx', 'rao_text.txt')
