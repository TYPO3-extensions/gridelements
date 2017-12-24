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


TSconfig
--------

It has been necessary to prevent children of Grid Elements from being
visible in the list view, due to serious problems that occured when
using the up and down arrows to move an element.  **Due to some
additional features we included in the list module this is not used
anymore and can be removed from your settings**

::

  TCEFORM.tt_content.tx_gridelements_backend_layout {
    removeChildrenFromList = 1
  }

These are the other TSconfig option you will get to even configure
Grid Elements completely without grid records:

.. ### BEGIN~OF~TABLE ###

.. _tsconfig:

tx\_gridelements
^^^^^^^^


.. _tsconfig-tx-gridelements-setup:

tx\_gridelements.setup
""""""""""""""""

.. container:: table-row

   Property
         setup

   Data type
         Grid TS structure

   Description
         Contains the setup of different grid layouts

   Default
         N/A

.. _tsconfig-tx-gridelements-setup-123:

tx\_gridelements.setup.123
""""""""""""""""

.. container:: table-row

   Property
         tx\_gridelements.setup.123

   Data type
         Grid TS structure

   Description
         Contains the setup of the grid layout with the ID 123 (see the GRID TS
         section for an example) – You can create the structure with the Grid
         Wizard and then copy it to your TSconfig.

   Default
         N/A

.. _tsconfig-tx-gridelements-overrulerecords:

tx\_gridelements.overruleRecords
""""""""""""""""

.. container:: table-row

   Property
         tx\_gridelements.overruleRecords

   Data type
         boolean

   Description
         Set this value to 1, when you want TSconfig settings to ave
         precendence over layout records with the same ID

   Default
         0

.. _tsconfig-tx-gridelements-excludelayoutids:

tx\_gridelements.excludeLayoutIds
""""""""""""""""

.. container:: table-row

   Property
         tx\_gridelements.excludeLayoutIds

   Data type
         List of strings

   Description
         A list of IDs of layouts that should not be available in this branch
         of the page tree

   Default
         N/A

.. _tsconfig-TCEFORM-tt-content-tx-gridelements-backend-layout-PAGE-TSCONFIG-ID:

TCEFORM.tt\_content.tx\_gridelements\_backend\_layout.PAGE\_TSCONFIG\_ID
""""""""""""""""

.. container:: table-row

   Property
         TCEFORM.tt\_content.tx\_gridelements\_backend\_layout.PAGE\_TSCONFIG\_ID

   Data type
         Integer

   Description
         The ID of the page that contains your layout records

   Default
         N/A


.. ###### END~OF~TABLE ######
