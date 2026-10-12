import zipfile
import xml.dom.minidom

def pretty_print_xml(docx_path, out_path):
    try:
        with zipfile.ZipFile(docx_path, 'r') as zip_ref:
            xml_content = zip_ref.read('word/document.xml')
            dom = xml.dom.minidom.parseString(xml_content)
            with open(out_path, 'w', encoding='utf-8') as f:
                f.write(dom.toprettyxml())
    except Exception as e:
        print(f"Error: {e}")

pretty_print_xml(r'storage\app\templates\resultats_ao.docx', 'rao_xml.txt')
