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

.. _TypoScript:


DataProcessing
--------------

This is the default TypoScript setting provided while including the
special **Gridelements w/DataProcessing** setup in your TS template editor:

::

  lib.gridelements.defaultGridSetup = FLUIDTEMPLATE
  lib.gridelements.defaultGridSetup {
    templateName.field = tx_gridelements_backend_layout
    templateName.ifEmpty = default
    layoutRootPaths {
      10 = EXT:gridelements/Resources/Private/Layouts/
    }
    partialRootPaths {
      10 = EXT:gridelements/Resources/Private/Partials/
    }
    templateRootPaths {
      10 = EXT:gridelements/Resources/Private/Templates/
    }
    dataProcessing {
      10 = GridElementsTeam\Gridelements\DataProcessing\GridChildrenProcessor
      10 {
        default {
          as = children
          # Default options of the grid children processor
          # Change them according to the needs of your layout
          # Read more about it in the TypoScript section of the manual
          # options {
            # sortingDirection = ASC
            # sortingField = sorting
            # recursive = 0
            # resolveFlexFormData = 1
            # resolveBackendLayout = 1
            # respectColumns = 1
            # respectRows = 1
          # }
        }
      }
    }
  }


As you can see, it is based on a usual FLUIDTEMPLATE with template, layout and partial paths.
It makes use of the GridChildrenProcessor to fetch children from the database.
Additionally this processor provides some internal keys to define the processing setup.
Anything else you want to use will be based on the official TypoScript syntax, since like the built in
processors of the core, the GridChildrtenProcessor might contain other processors too, so you won't have
to hassle with any other extension specific parameters.

As described in the commented part, there are some default settings for
those parameters, that will be used if you don't set any values yourself.
Just use the the debug viewhelper in the dummy templates to get an overview of the different behaviours.
::
  <f:debug>{_all}</f:debug>

Any of the internal keys and the default settings will of course be
passed to the stdWrap method, so you can assign almost anything to any
part of your setup.

The two setups for the shortcut cObject are the same as for the well known default setup.


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   Reference/Index

