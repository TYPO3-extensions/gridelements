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


Example 2
^^^^^^^^^


Example 2 - Element with child content and additional input (partly recommended)
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

This setup will add some simple input fields to the form of a Grid
Element. This element will create some jQuery based tabs in the
frontend output of the webpage later on:

::

  <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
  <T3DataStructure>
    <ROOT type="array">
      <type>array</type>
      <el type="array">
        <tabheader_1 type="array">
          <TCEforms type="array">
            <config type="array">
              <type>input</type>
              <size>48</size>
              <eval>trim</eval>
            </config>
            <label>Tab1:</label>
          </TCEforms>
        </tabheader_1>
  ...
        <tabheader_5 type="array">
          <TCEforms type="array">
            <config type="array">
              <type>input</type>
              <size>48</size>
              <eval>trim</eval>
            </config>
          <label>Tab5:</label>
        </TCEforms>
      </tabheader_5>
    </el>
  </ROOT>
  </T3DataStructure>


The backend form of this setup will be looking like this:

|img-19|

Take a look at the TypoScript section to find out how this would be
rendered in the frontend.
