#! /usr/bin/env python3
# ^

import os, sys, re
from PyQt4.QtGui import *
from libxmp import XMPFiles, consts, XMPMeta
from xml.sax.saxutils import escape

# This function reads all xmp metadata and writes them to an external file
def internal_to_sidecar():

    if os.path.isfile(fileEdit.text()) == False:
        editLabel.setText ("Please enter a filepath into the file field")
        return

    filename = fileEdit.text()
    print(filename)
    
    xmpfile = XMPFiles( file_path=filename, open_forupdate=False)
    xmp = xmpfile.get_xmp()
    
    # Create external file and write to it
    handle = open (os.path.basename(filename) + '.xml', 'w')
    handle.write(str(xmp))
    handle.close()
    
    xmpfile.close_file()

    fulltextEdit.setText (str(xmp))
    editLabel.setText ("Loaded " + filename + " and created sidecar file at " + filename + ".xml")

    loadxmp_to_editor()
    
def loadxmp_to_editor ():

    xmpxml = fulltextEdit.toPlainText()

    # Get creator
    start = xmpxml.find("<dc:creator>")
    end = xmpxml.find("</dc:creator>")
    creatorEdit.setText(re.sub("<.*?>", " ", xmpxml[start:end]).strip())

    # Get title
    start = xmpxml.find("<dc:title>")
    end = xmpxml.find("</dc:title>")
    titleEdit.setText(re.sub("<.*?>", " ", xmpxml[start:end]).strip())

    # Get source
    start = xmpxml.find("<dc:source>")
    end = xmpxml.find("</dc:source>")

    references = re.sub("<.*?>", " ", xmpxml[start:end]).strip().split("\n")
    references2 = []
    for i in references:
        i = i.strip()
        references2.append(i + "\n")
    
    referencesEdit.setText(''.join(references2))

def write_to_file():

    filename = fileEdit.text()
    
    if os.path.isfile(filename) == False:
        print("Error: Media file or reference file missing")
    else:
        xmpfile = XMPFiles( file_path=filename, open_forupdate=True)

        xmp = XMPMeta()

        # Write creator
        xmp.append_array_item(consts.XMP_NS_DC, 'creator', escape(str(creatorEdit.text())), {'prop_array_is_ordered': True, 'prop_value_is_array': True})

        # Write title
        xmp.append_array_item(consts.XMP_NS_DC, 'title', escape(str(titleEdit.text())), {'prop_array_is_ordered': True, 'prop_value_is_array': True})

        # Write sources
        for line in referencesEdit.toPlainText().split("\n"):
            if (len(line) > 1):
                xmp.append_array_item(consts.XMP_NS_DC, 'source', escape(str(line)), {'prop_array_is_ordered': True, 'prop_value_is_array': True})
        """
        if (xmpfile.can_put_xmp(xmp) == False):
            editLabel.setText ('Error: Cannot write metadata to file.')
        else:
        """
        xmpfile.put_xmp(xmp)
        xmpfile.close_file()
        editLabel.setText ('Success! Metadata written to file.')


if __name__ == "__main__":
    a = QApplication(sys.argv)
    window = QWidget()
    window.setWindowTitle("Metadata Editor")
    window.show()

    css = """
        QWidget {
            Background: white;
        }
        QLineEdit, QTextEdit{
            Background: white;
            color:black;
            border:1px solid #ddd;
            border-radius: 3px;
            height: 22px;
        }
        QLineEdit:hover, QTextEdit:hover{
            Background: #ddd;
        }
        QPushButton{
            background:#555;
            color:#fff;
            border-radius: 3px;
            display:block;
            padding:5px;
        }
        QPushButton:hover{
            background-color:#888;
        }
        QFrame.VLine{
            background-color:#888;
        }
    """

    a.setStyleSheet(css) 

    # Creating widgets

    fileLabel = QLabel("File:")
    fileEdit = QLineEdit()

    creatorLabel = QLabel("Creator:")
    creatorEdit = QLineEdit()
    titleLabel = QLabel("Title:")
    titleEdit = QLineEdit()
    referencesLabel = QLabel("References:")
    referencesEdit = QTextEdit()
    btn1 = QPushButton("Load")
    btn2 = QPushButton("Save")

    fulltextLabel = QLabel("Raw XMP Metadata:")
    fulltextEdit = QTextEdit()

    editLabel = QLabel("Last Action: None")

    layout = QGridLayout(window)

    layout.addWidget(fileLabel, 0, 0)
    layout.addWidget(fileEdit, 1, 0)
    
    layout.addWidget(creatorLabel, 0, 2)
    layout.addWidget(creatorEdit, 0, 3)
    layout.addWidget(titleLabel, 1, 2)
    layout.addWidget(titleEdit, 1, 3)
    layout.addWidget(referencesLabel, 2, 2)
    layout.addWidget(referencesEdit, 2, 3)
    layout.addWidget(btn1, 6, 0)    
    layout.addWidget(btn2, 6, 2, 1, 2)
    
    layout.addWidget(fulltextLabel, 0, 5)
    layout.addWidget(fulltextEdit, 1, 5, 5, 1)
    
    layout.addWidget(editLabel, 11, 0, 1, 6)

    # Spaces
    verticalLine = QFrame ()
    verticalLine.setFrameStyle(QFrame.VLine)
    layout.addWidget(verticalLine, 0, 1, 7, 1)
    verticalLine2 = QFrame ()
    verticalLine2.setFrameStyle(QFrame.VLine)
    layout.addWidget(verticalLine2, 0, 4, 7, 1)

    horizontalLine = QFrame ()
    horizontalLine.setFrameStyle(QFrame.VLine)
    layout.addWidget(horizontalLine, 10, 0, 1, 6)

    layout.addWidget(fileLabel, 0, 0)
    
    # Bind click on button
    btn1.clicked.connect(internal_to_sidecar)
    btn2.clicked.connect(write_to_file)

    window.resize(640, 180)
    
    sys.exit(a.exec_())
