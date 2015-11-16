

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

.. _Flexform:


Flexform
--------

Each time you want a Grid Element to be more than just a structured
multicolumn container with lots of different content elements, you can
make use of the  **Flexform Configuration** field of the CE backend
layout record.

We thought that even though it is no good idea to store relations as
CSV lists in XML structures within a database field, flexforms are
still very useful when it comes to configurational stuff.

Anything that has nothing to do with the relation to the actual
content elements can be put here using the same syntax as in any other
flexform field:

- Checkboxes and/or radio buttons to enable or disable certain
  behaviours

- Selectors to get different variants of the Grid Element in the
  frontend

- Input fields for additional information besides the usual content
  elements

- Textareas for internal notes to the editors

You could even copy and paste TemplaVoila data structures here, which
might be helpful during a migration process from FCEs to Grid
Elements.

Anything defined in the configuration will show up in the  **Plugin
Options** of the Grid Element's editing form.

Currently any type of form field value will be transferred to the data
set of the Grid Element record while rendering it in the frontend.
They will be prefixed with “ **flexform\_** ” to make sure that they
don't override any other field with the same name, but still can be
accessed via the usual TypoScript functions.

Sections are not supported by the frontend rendering process yet.


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   Example/Index

