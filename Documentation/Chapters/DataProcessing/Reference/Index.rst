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


.. _typoscript:

TypoScript
^^^^^^^^^^


.. _typoscript-grid-children-processor:

setup
""""""""""""""""

.. container:: table-row

   Property
         dataProcessing

   Data type
         array of class references by full namespace

   Description
         Add one or multiple processors to manipulate the $data variable of the currently rendered content object, like tt_content or page. The sub- property options can be used to pass parameters to the processor class.

   Default
         10 = GridElementsTeam\\Gridelements\\DataProcessing\\GridChildrenProcessor


.. _typoscript-dataprocessing-default:

dataProcessing.123.default
""""""""""""""""""""""""""

.. container:: table-row

   Property
         default

   Data type
         Internal

   Description
         The default setup used by any Grid Element layout that has not got its
         own setup available. Layouts are assigned by their identifier.
         Just provide individual blocks **myIdentifier{...}** for each layout.

   Default
         default


.. _typoscript-dataprocessing-default-as:

dataProcessing.123.default.as
"""""""""""""""""""""""""""""

.. container:: table-row

   Property
         as

   Data type
         Internal / stdWrap

   Description
         This will be the name of the variable filled with the output generated
         by the GridChildrenProcessor. You can access it via **{children}** from
         within your Fluid template.

   Default
         children


.. _typoscript-dataprocessing-default-options:

dataProcessing.123.default.options
""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         options

   Data type
         Internal

   Description
         Can contain any of the following options to determine the behaviour
         of the GridChildrenProcessor.

   Default
         N\A


.. _typoscript-dataprocessing-default-options-sortingDirection:

dataProcessing.123.default.options.sortingDirection
"""""""""""""""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         sortingDirection

   Data type
         String / stdWrap

   Description
         Determines the sorting direction of the database query for children
         Must be either **desc** or **asc**. Other values will be ignored.

   Default
         asc


.. _typoscript-dataprocessing-default-options-sortingField:

dataProcessing.123.default.options.sortingDirection
"""""""""""""""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         sortingField

   Data type
         String / stdWrap

   Description
         Determines the sorting field of the database query for children.
         Can be any fieldname but you have to make sure that the field exists
         yourself, otherwise the query might fail.

   Default
         sorting


.. _typoscript-dataprocessing-default-options-recursive:

dataProcessing.123.default.options.recursive
""""""""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         recursive

   Data type
         Integer / stdWrap

   Description
         Determines the depth to fetch even children of child records
         recursively. This way you can use complex nested data structures
         within a single FLUIDTEMPLATE. The whole tree will be handed over
         to the variable as a data array.

   Default
         0


.. _typoscript-dataprocessing-default-options-resolveFlexFormData:

dataProcessing.123.default.options.resolveFlexFormData
""""""""""""""""""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         resolveFlexFormData

   Data type
         Boolean / stdWrap

   Description
         Determines if FlexForms of children should be resolved and assigned
         to virtual fields named **flexform\_my\_fieldname**.

   Default
         1


.. _typoscript-dataprocessing-default-options-resolveBackendLayout:

dataProcessing.123.default.options.resolveBackendLayout
"""""""""""""""""""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         resolveBackendLayout

   Data type
         Boolean / stdWrap

   Description
         Determines if CE-BackendLayouts of children should be resolved and
         assigned to virtual fields named **tx\_gridelements\_backend\_layout\_resolved**.
         Use this information i.e. to generate CSS classes based on layout data.

   Default
         1


.. _typoscript-dataprocessing-default-options-respectColumns:

dataProcessing.123.default.options.respectColumns
"""""""""""""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         respectColumns

   Data type
         Boolean / stdWrap

   Description
         Determines if layout columns should be recognized and assigned while
         rendering children. Adds the array key **columns** to the **children** array
         and renders children into their respective columns.

   Default
         1


.. _typoscript-dataprocessing-default-options-respectRows:

dataProcessing.123.default.options.respectRows
""""""""""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         respectRows

   Data type
         Boolean / stdWrap

   Description
         Determines if layout rows should be recognized and assigned while
         rendering columns. Adds the array key **rows** to the **children** array
         and the array key **columns** to each row.
         Then it renders children into their respective columns.
         Sets respectColumns internally if not set.

   Default
         1


.. ###### END~OF~TABLE ######


