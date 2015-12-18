

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

The colPos field of the tt\_content table will be changed from unsigned int(3) to smallint(6) to enable the usage of negative values. This should **never be reverted** by any upgrade script later on! Otherwise any child element will be moved from it's parent container to the default page column. Some people ran into problems during automatic upgrades done by their providers. So be sure to make a backup of your content before upgrading!

Important note about the Drag & Drop behaviour!
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The D&D behaviour as well as the **create new element**, **paste** and **copy from another page** buttons have been adjusted to the new core way of handling localizations and translations.

This means: If you did not explicitely accept inconsistent language handling by setting
 
``mod.web_layout.allowInconsistentLanguageHandling``

in your Page TSconfig, there are two options:

1. You selected **Translate** to create records in another language that are connected to their default language parent. In this case you won't get any option to create new elements, paste something from somewhere else or drop existing elements into a language column other than default.

2. You selected **Copy** to create records in another language that are not connected to any parent but just plain copies - In this case will get all the features of the default language in the translated language as well - you can even drag & drop elements between different languages then and they will change their language ID accordingly

The latter will be active as well, when you went for inconsistent language handling.