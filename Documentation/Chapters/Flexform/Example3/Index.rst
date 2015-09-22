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


Example 3
^^^^^^^^^


Example 3 - a basic “FCE” - not recommended(!) but anyway here it is
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

An often asked task, especially if you migrate from TemplaVoila, will
be to provide the editors with the predefined content elements (FCEs)
they are used to. This can be done by completely defining the
structure as a flexform and handling it via TypoScript. You should by
any means avoid these FCEs, since you will lose any advantage of the
normalized Grid Elements approach. Actually Flexform fields should
**not** contain any real content elements but just configurational
stuff like checkboxes, radiobuttons and selectboxes.

Still this setup will define some input fields to render a very basic
teaser element in the frontend. It uses a headline, a link, an image
and some text.

::

  <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
  <T3DataStructure>
    <ROOT type="array">
      <type>array</type>
      <el type="array">
        <headline type="array">
          <TCEforms type="array">
            <label>Headline</label>
            <config type="array">
              <type>input</type>
              <size>48</size>
              <eval>trim</eval>
            </config>
          </TCEforms>
        </headline>
        <image type="array">
          <TCEforms type="array">
            <label>Image</label>
            <config type="array">
              <type>group</type>
              <internal_type>file</internal_type>
              <allowed>jpg,gif,png</allowed>
              <max_size>2000</max_size>
              <uploadfolder>uploads/tx_gridelements</uploadfolder>
              <show_thumbs>1</show_thumbs>
              <size>3</size>
              <minitems>0</minitems>
              <maxitems>1</maxitems>
            </config>
          </TCEforms>
        </image>
        <link type="array">
          <TCEforms type="array">
            <config type="array">
              <type>input</type>
              <eval>trim</eval>
              <wizards type="array">
                <_PADDING>2</_PADDING>
                <link type="array">
                  <type>popup</type>
                  <title>Link</title>
                  <icon>link_popup.gif</icon>
                  <script>
                    browse_links.php?mode=wizard&amp;act=page
                  </script>
                  <params type="array">
                    <blindLinkOptions>
                      file,spec,email,folder
                    </blindLinkOptions>
                  </params>
                  <JSopenParams>
                    height=300,width=500,status=0,¬menubar=0,scrollbars=1
                  </JSopenParams>
                </link>
              </wizards>
            </config>
            <label>Link</label>
          </TCEforms>
        </link>
        <text type="array">
          <TCEforms type="array">
            <config type="array">
              <type>text</type>
              <cols>40</cols>
              <rows>10</rows>
              <wrap>off</wrap>
            </config>
            <label>Text</label>
          </TCEforms>
        </text>
      </el>
    </ROOT>
  </T3DataStructure>


Be aware of wrapped lines. For a working, easy to copy scripting, have
a look at https://gist.github.com/3294052.The backend form of this
setup will look like this:

|img-20|

Take a look at the TypoScript section to find out how this would be
rendered in the frontend.
