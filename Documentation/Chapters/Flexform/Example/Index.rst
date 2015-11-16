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


Example
^^^^^^^^^


Element with child content and handling settings
""""""""""""""""""""""""""""""""""""""""""""""""

To get this kind of structure for a Grid Element box with special
features …

|img-18|

… you will need this setup. It will create some special boxes in the
frontend, that can have frames in different selectable colors and an
additional jQuery based accordion that can be enabled by the user:

::

  <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
  <T3DataStructure>
    <ROOT type="array">
      <type>array</type>
      <el type="array">
        <color type="array">
          <TCEforms type="array">
            <label>Box Color</label>
            <config type="array">
              <type>select</type>
              <items type="array">
                <numIndex index="0" type="array">
                  <numIndex index="0">Red</numIndex>
                  <numIndex index="1">1</numIndex>
                </numIndex>
                <numIndex index="1" type="array">
                  <numIndex index="0">Green</numIndex>
                  <numIndex index="1">2</numIndex>
                </numIndex>
              </items>
            </config>
          </TCEforms>
        </color>
        <accordion type="array">
          <TCEforms type="array">
            <label>Accordion Effect</label>
            <config type="array">
              <type>check</type>
              <default>0</default>
            </config>
          </TCEforms>
        </accordion>
        <class type="array">
          <TCEforms type="array">
            <config type="array">
              <type>input</type>
              <size>48</size>
              <eval>trim</eval>
            </config>
            <label>CSS class</label>
          </TCEforms>
        </class>
      </el>
    </ROOT>
  </T3DataStructure>
