

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


Reference
---------

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         Property:

   Data type
         Data type:

   Description
         Description:

   Default
         Default:


.. container:: table-row

   Property
         setup

   Data type
         Internal /stdWrap

   Description
         The setup handed over to the userFunc that is responsible for the
         rendering process

   Default
         N/A


.. container:: table-row

   Property
         setup.default

   Data type
         Internal / stdWrap

   Description
         The default setup used by any Grid Element layout that has not got its
         own setup available. Layouts are assigned by their UID

         It usually contains the columns parameter and might use additional
         stdWrap methods. If you provide a cObject as stdWrap method, this will
         override the default output and give you the option to make use of the
         special virtual fields containing prerendered stuff.

   Default
         N/A


.. container:: table-row

   Property
         setup.123

   Data type
         Internal / stdWrap

   Description
         This setup will be used for the Grid Element layout with the UID value
         123

   Default
         N/A


.. container:: table-row

   Property
         columns

   Data type
         Internal / stdWrap

   Description
         This contains the setup for the default column and any other column
         that might differ from the default. Columns are assigned by their
         column value.

   Default
         N/A


.. container:: table-row

   Property
         columns.default

   Data type
         Internal / stdWrap

   Description
         This will be the setup used for any column that has not got its own
         setup available

   Default
         N/A


.. container:: table-row

   Property
         columns.123

   Data type
         Internal / stdWrap

   Description
         This will be used for the column with the column value 123

   Default
         N/A


.. container:: table-row

   Property
         renderObj

   Data type
         cObject / stdWrap

   Description
         Can be any kind of TypoScript content object, like TEXT or TEMPLATE

   Default
         COA


.. container:: table-row

   Property
         flexform\_fieldname

   Data type
         Flexform field

   Description
         Get the value of a field defined in the flexform of the grid layout of
         the current cObj

   Default
         N/A


.. container:: table-row

   Property
         parentgrid\_fieldname

   Data type
         Value of the parent container

   Description
         Get the value of a field of the parent container of the current cObj

   Default
         N/A


.. container:: table-row

   Property
         tx\_gridelements\_view\_children

   Data type
         Array

   Description
         An array containing all the children as raw data

   Default
         N/A


.. container:: table-row

   Property
         tx\_gridelements\_view\_columns

   Data type
         Array

   Description
         An array containing the prerendered columns

   Default
         N/A


.. container:: table-row

   Property
         tx\_gridelements\_view\_column\_123

   Data type
         HTML

   Description
         The prerendered content of column 123

   Default
         N/A


.. container:: table-row

   Property
         tx\_gridelements\_view\_child\_123

   Data type
         HTML

   Description
         The prerendered child with the uid 123

   Default
         N/A


.. ###### END~OF~TABLE ######


