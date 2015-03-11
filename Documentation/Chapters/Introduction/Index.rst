

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


Introduction
^^^^^^^^^^^^

Grid View
"""""""""

Since version 4.5 the TYPO3 core offers the so called  **grid view**,
a feature developed during the user experience week, that gives
backend users some nice options to get a more  **user friendly backend
layout**. You can create your own table based backend layout records,
fill in as many columns as you like with either a wizard or a
*TSconfig* like code and arrange these columns to match your desired
layout, so backend users will easily recognize where to put their
content. Each record can get an icon that will be used as with the
layout selector box.


Grid Elements are pushing these features to the next level, namely content elements.
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

You will get pretty much the same backend layout records, again
created with a wizard or by hand. By assigning such a layout to a Grid
Element, you can enable a table based structure for this element,
which is becoming a container this way. This container is offering
different  **cells for your content elements** , which can of course
be Grid Elements as well. Setting up  **nested structures is a
breeze** this way. Each record can get a second icon that will be used
for the detailed description within the new content element wizard.
Additionally CE backend layouts can contain a  **flexform** to add
lots of different features, like form based select boxes and switches
to control the frontend output of a grid elements based on this layout
later on.

Another usability improvement of Grid Elements is the new  **drag and
drop behavior** added to the page module. You can drag elements
between different columns within the page or element grid. Drop an
element to move it or make a copy by pressing the CTRL-key while
dropping. You can drag in new content elements from a new content
element wizard overlay, that can be activated by the  *add new content
element* button on top of the page module. You can create references
to content elements on the same or another page with icons appearing
on top of each column as soon as an element is available from the
normal clipboard. And of course you can have the so called  *unused
elements* as well, by simply adding a column with colPos -2 to your
page grid.


A short roundup of the features and advantages
""""""""""""""""""""""""""""""""""""""""""""""

- Completely TypoScript based backend layout

- Comfortable point and click wizard to create backend layout structures
  and assign columns and allowed content types

- Completely XML- and CSV-less normalized relations between containers
  and elements

- Flexforms used for configurational stuff only, can be derived from
  existing data structures

- Original colPos and sorting fields still working

- Grid and backend layout aware list module with top level sorting that
  can list children of containers as nested list via AJAX

- Top level layouts to exclude certain types of Grid Elements from being
  used within other Grid Elements

- Drag & drop move and copy actions for the page module

- Get me a copy from another page icon so you won't have to switch pages
  when fetching content

- New content element wizard overlay to drag in new content elements

- Paste icons for pasting copies and references into grid columns

- References to complete pages can be used within the reference element

- Referenced content visible in the preview section of the reference
  element

- Completely TypoScript based frontend output

- Flexform field values automatically added to the data set

- Prerendered content and arrays added to the data set to be used even
  with other templating engines like Smarty or Fluid

- No need for HTML templates and mapping to get a backend layout and/or
  frontend output

- Completely based on hooks without XCLASSing (only exception is the
  list module due to missing hooks)

Some of you might be used to similar features of TemplaVoilaand ask
themselves why they should be using grid elements instead. If you want
to know more details, check out the  **FAQ** section to find some
answers
