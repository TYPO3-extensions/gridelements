.. include:: Images.txt

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
   :class:  typoscript
.. role::   php(code)


Example
^^^^^^^

Example 3 - a basic “FCE” continued
"""""""""""""""""""""""""""""""""""

This part shows the TypoScript to render the output for the frontend.
The expected result will look like this:

|img-21|

The TypoScript is like this:

::

  tt_content.gridelements_pi1.20.10.setup {
    1 < lib.gridelements.defaultGridSetup
    1 {
      prepend = COA
      prepend {
        10 = TEXT
        10 {
          data = field:flexform_headline
          typolink.parameter.data = field:flexform_link
          wrap = <h3>\|</h3>
        }
        20 = IMAGE
        20 {
          file.import.data = field:flexform_image
          file.width = 200
          file.height = 133c
        }
        30 = COA
        30 {
          10 = TEXT
          10 {
            data = field:flexform_text
            crop = 150 | &nbsp; | 1
          }
          20 = TEXT
          20 {
            value = &nbsp;more...
            typolink.parameter.data = field:flexform_link
          }
          wrap = <p>\|</p>
        }
      }
    }
  }


The easy to copy scripting can be found at
`https://gist.github.com/3294347 <https://gist.github.com/3294347>`_

The “1” in line 2 and 3 reflect the uid of the gridelements record,
fit this to your needs.

