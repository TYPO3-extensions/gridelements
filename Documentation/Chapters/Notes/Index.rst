

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


Notes
-----


Important note about the colPos field!
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The colPos field of the tt\_content table will be changed from
unsigned int(3) to smallint(6) to enable the usage of negative values.
This should  **never be reverted** by any upgrade script later on!
Otherwise any child element will be moved from it's parent container
to the default page column. Some people ran into problems during
automatic upgrades done by their providers. So be sure to make a
backup of your content before upgrading!

