<?php $page_title = 'Typography';
include(dirname(__FILE__) . '/_wrapper-head.php'); ?>

<div class="secondary-sidebar-container">
	<div class="container typography" id="markdown">

    <h2 class="with-underline">Font Stacks</h2>

    <p><code>$font-family</code></p>
    <p>
      -apple-system, BlinkMacSystemFont, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif<br>
      <em>italic</em>, <strong>bold</strong>, <strong><em>bold italic</em></strong>
    </p>

    <p><code>$font-family-monospace</code></p>
    <p class="font-family-monospace">
      'SFMono-Regular', Menlo, Monaco, Consolas, "Courier New", monospace<br>
      <em>italic</em>, <strong>bold</strong>, <strong><em>bold italic</em></strong>
    </p>

    <h2 class="with-underline">Headings</h2>

    <h1>h1. First Level Heading <small>Secondary text</small></h1>

    <h2>h2. Second Level Heading <small>Secondary text</small></h2>

    <h3>h3. Third Level Heading <small>Secondary text</small></h3>

    <h4>h4. Fourth Level Heading <small>Secondary text</small></h4>

    <h5>h5. Fifth Level Heading <small>Secondary text</small></h5>

    <h6>h6. Sixth Level Heading <small>Secondary text</small></h6>

    <h2 class="with-underline">Body Copy</h2>

    <p class="lead">This introductory paragraph uses the <code>.lead</code> class to stand out. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Ut enim ad minim veniam, quis nostrud exercitation ullamco.</p>

    <p>You can use the <code>mark</code> tag to <mark>highlight</mark> text. You can use the <code>del</code> tag to indicate <del>deleted text</del> and the <code>s</code> tag to indicate <s>text that is no longer relevant.</s> Use the <code>ins</code> tag for <ins>text that's meant to be treated as an addition to the document.</ins> And for a standard <u>text underline</u> use the <code>u</code> tag. <strong>Bold text</strong> can be rendered using the <code>strong</code> tag. And <em>emphasized italic text</em> is rendered with the <code>em</code> tag.</p>

    <pre><code>$ php system/ee/eecms upgrade</code></pre>

    <p>Donec viverra auctor lobortis. <a href="">This is an example of a link.</a> Pellentesque eu est a nulla placerat dignissim. Morbi a enim in magna semper bibendum. Etiam scelerisque, nunc ac egestas consequat, odio nibh euismod nulla, eget auctor orci nibh vel nisi. Aliquam erat volutpat. Mauris vel neque sit amet nunc gravida congue sed sit amet purus. Quisque lacus quam, egestas ac tincidunt a, lacinia vel velit. Aenean facilisis nulla vitae urna tincidunt congue sed ut dui. Morbi malesuada nulla nec purus convallis consequat. Vivamus id mollis quam. Morbi ac commodo nulla. In condimentum orci.</p>

    <h2 class="with-underline">Lists</h2>

    <h3>Unordered List</h3>

    <ul>
      <li>Lorem ipsum dolor sit amet</li>
      <li>Consectetur adipiscing elit</li>
      <li>Integer molestie lorem at massa</li>
      <li>Facilisis in pretium nisl aliquet</li>
      <li>Nulla volutpat aliquam velit
        <ul>
          <li>Phasellus iaculis neque</li>
          <li>Purus sodales ultricies</li>
          <li>Vestibulum laoreet porttitor sem</li>
          <li>Ac tristique libero volutpat at</li>
        </ul>
      </li>
      <li>Faucibus porta lacus fringilla vel</li>
      <li>Aenean sit amet erat nunc</li>
      <li>Eget porttitor lorem</li>
    </ul>

    <h3>Ordered List</h3>

    <ol>
      <li>Lorem ipsum dolor sit amet</li>
      <li>Consectetur adipiscing elit</li>
      <li>Integer molestie lorem at massa</li>
      <li>Facilisis in pretium nisl aliquet</li>
      <li>Nulla volutpat aliquam velit
        <ol>
          <li>Phasellus iaculis neque</li>
          <li>Purus sodales ultricies</li>
          <li>Vestibulum laoreet porttitor sem</li>
          <li>Ac tristique libero volutpat at</li>
        </ol>
      </li>
      <li>Faucibus porta lacus fringilla vel</li>
      <li>Aenean sit amet erat nunc</li>
      <li>Eget porttitor lorem</li>
    </ol>

	</div>
</div>

<?php include(dirname(__FILE__) . '/_wrapper-footer.php'); ?>
