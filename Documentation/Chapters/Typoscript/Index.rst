

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


TypoScript
----------

This is the default TypoScript setting provided while including its
setup in your TS template editor:

::

  lib.gridelements.defaultGridSetup {
    // stdWrap functions being applied to each element

    columns {
      default {
        renderObj = COA
        renderObj {
          # You can use registers to i.e. provide different image settings for each column
          # 10 = LOAD_REGISTER
          20 =< tt_content

          # And you can reset the register later on
          # 30 = RESTORE_REGISTER
        }
      }
    }

    # if you want to provide your own templating, just insert a cObject here
    # this will prevent the collected content from being rendered directly
    # i.e. cObject = TEMPLATE or cObject = FLUIDTEMPLATE will be available from the core
    # the content will be available via fieldnames like
    # tx_gridelements_view_columns (an array containing each column)
    # or tx_gridelements_view_children (an array containing each child)
    # tx_gridelements_view_column_123 (123 is the number of the column)
    # or tx_gridelements_view_child_123 (123 is the UID of the child)

  }

  lib.tt_content.shortcut.pages = COA
  lib.tt_content.shortcut.pages {
    10 = USER
    10 {
      userFunc = tx_gridelements_view->user_getTreeList
    }
    20 = CONTENT
    20 {
      table = tt_content
      select {
        pidInList.data = register:pidInList
        where = colPos >= 0
        orderBy = colPos,sorting
        orderBy.dataWrap = FIND_IN_SET(pid,'{register:pidInList}'),|
      }
    }
  }

  tt_content.shortcut.5 = LOAD_REGISTER
  tt_content.shortcut.5 {
    tt_content_shortcut_recursive.field = recursive
  }

  tt_content.shortcut.20 {
    0 {
      tables := addToList(pages)
      conf.pages < lib.tt_content.shortcut.pages
    }
    1 {
      tables := addToList(pages)
      conf.pages < lib.tt_content.shortcut.pages
    }
  }

  tt_content.gridelements_pi1 >
  tt_content.gridelements_pi1 = COA
  tt_content.gridelements_pi1 {
    #10 =< lib.stdheader
    20 = COA
    20 {
      10 = USER
      10 {
        userFunc = tx_gridelements_view->main
        setup {
          default < lib.gridelements.defaultGridSetup
        }
      }
    }
  }


As you can see, it is just based on the usual TypoScript and uses some
internal keys, like columns, default and renderObj to define the setup
for the columns. Anything else you want to use will be based on the
official TypoScript syntax, so you won't have to hassle with any
extension specific parameters.

As described in the commented part, you will find some additional
virtual fields in your data, that will contain stuff that has been
used during the rendering process. These come in handy, when you want
to use a TEMPLATE or FLUIDTEMPLATE element to produce your output.

Any of the internal keys and the default settings will of course be
passed to the stdWrap method, so you can assign almost anything to any
part of your setup.

The two setups for the shortcut cObject are used to render the
references properly, so you should probably not change them unless you
really know what you are doing.


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   Reference/Index

