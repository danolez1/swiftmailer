<?php

require_once 'Swift/AbstractSwiftUnitTestCase.php';
require_once 'Swift/Mime/Header/AddressHeader.php';
require_once 'Swift/Mime/HeaderEncoder.php';

Mock::generate('Swift_Mime_HeaderEncoder', 'Swift_Mime_MockHeaderEncoder');

class Swift_Mime_Header_AddressHeaderTest
  extends Swift_AbstractSwiftUnitTestCase
{
  
  private $_charset = 'utf-8';
  
  public function testEmptyGroupCanBeDefined()
  {
    /* -- RFC 2822, 3.4.
     address         =       mailbox / group
     
     .....
     
     group           =       display-name ":" [mailbox-list / CFWS] ";"
                        [CFWS]
     */
    
    $header = $this->_getHeader('To');
    $header->defineGroup('undisclosed-recipients');
    $this->assertEqual(array(), $header->getGroup('undisclosed-recipients'));
    $this->assertEqual('undisclosed-recipients:;', $header->getPreparedValue());
  }
  
  public function testAddressGroupCanBeDefined()
  {
    $header = $this->_getHeader('Cc');
    $header->defineGroup('Admin Dept',
      array('tim@swiftmailer.org' => 'Tim Fletcher',
      'andrew@swiftmailer.org' => 'Andrew Rose')
      );
    $this->assertEqual(array('tim@swiftmailer.org' => 'Tim Fletcher',
      'andrew@swiftmailer.org' => 'Andrew Rose'),
      $header->getGroup('Admin Dept')
      );
    $this->assertEqual(
      'Admin Dept:Tim Fletcher <tim@swiftmailer.org>, ' .
      'Andrew Rose <andrew@swiftmailer.org>;',
      $header->getPreparedValue()
      );
  }
  
  public function testValueIncludesGroupAsItemInAddressList()
  {
    $header = $this->_getHeader('Cc', array('chris@swiftmailer.org'));
    $header->defineGroup('Admin Dept',
      array('tim@swiftmailer.org' => 'Tim Fletcher',
      'andrew@swiftmailer.org' => 'Andrew Rose')
      );
    $this->assertEqual(
      'Admin Dept:Tim Fletcher <tim@swiftmailer.org>, ' .
      'Andrew Rose <andrew@swiftmailer.org>;, chris@swiftmailer.org',
      $header->getPreparedValue()
      );
  }
  
  public function testAddressesFromGroupAreMerged()
  {
    $header = $this->_getHeader('Cc', array('chris@swiftmailer.org'));
    $header->defineGroup('Admin Dept',
      array('tim@swiftmailer.org' => 'Tim Fletcher',
      'andrew@swiftmailer.org' => 'Andrew Rose')
      );
    $this->assertEqual(
      array('chris@swiftmailer.org', 'tim@swiftmailer.org', 'andrew@swiftmailer.org'),
      $header->getAddresses()
      );
  }
  
  public function testNameAddressesFromGroupAreMerged()
  {
    $header = $this->_getHeader('Cc', array('chris@swiftmailer.org'));
    $header->defineGroup('Admin Dept',
      array('tim@swiftmailer.org' => 'Tim Fletcher',
      'andrew@swiftmailer.org' => 'Andrew Rose')
      );
    $this->assertEqual(
      array(
        'chris@swiftmailer.org' => null,
        'tim@swiftmailer.org' => 'Tim Fletcher',
        'andrew@swiftmailer.org' => 'Andrew Rose'
        ),
      $header->getNameAddresses()
      );
  }
  
  public function testNameAddressStringsFromGroupAreMerged()
  {
    $header = $this->_getHeader('Cc', array('chris@swiftmailer.org'));
    $header->defineGroup('Admin Dept',
      array('tim@swiftmailer.org' => 'Tim Fletcher',
      'andrew@swiftmailer.org' => 'Andrew Rose')
      );
    $this->assertEqual(
      array(
        'chris@swiftmailer.org',
        'Tim Fletcher <tim@swiftmailer.org>',
        'Andrew Rose <andrew@swiftmailer.org>'
        ),
      $header->getNameAddressStrings()
      );
  }
  
  public function testAddressesInGroupsDoNotAppearElsewhere()
  {
    $header = $this->_getHeader('To',
      array('chris@swiftmailer.org' => 'Chris Corbyn',
        'fred@swiftmailer.org' => 'Fred')
      );
    $header->defineGroup('Admin Dept',
      array('tim@swiftmailer.org' => 'Tim Fletcher',
      'andrew@swiftmailer.org' => 'Andrew Rose',
      'chris@swiftmailer.org')
      );
    $this->assertEqual(
      'Admin Dept:Tim Fletcher <tim@swiftmailer.org>, ' .
      'Andrew Rose <andrew@swiftmailer.org>, ' .
      'chris@swiftmailer.org;, ' .
      'Fred <fred@swiftmailer.org>',
      $header->getPreparedValue()
      );
  }
  
  public function testRemoveAddressesRemovesFromGroups()
  {
    $header = $this->_getHeader('To',
      array('fred@swiftmailer.org' => 'Fred')
      );
    $header->defineGroup('Admin Dept',
      array('tim@swiftmailer.org' => 'Tim Fletcher',
      'andrew@swiftmailer.org' => 'Andrew Rose',
      'chris@swiftmailer.org')
      );
    $header->removeAddresses(
      array('tim@swiftmailer.org', 'fred@swiftmailer.org')
      );
    $this->assertEqual(array('andrew@swiftmailer.org', 'chris@swiftmailer.org'),
      $header->getAddresses()
      );
  }
  
  public function testAddressesCanBeHiddenFromDisplay()
  {
    $header = $this->_getHeader('To',
      array(
        'chris@swiftmailer.org' => 'Chris Corbyn',
        'fred@swiftmailer.org' => 'Fred'
        )
      );
    $header->setHiddenAddresses('fred@swiftmailer.org');
    $this->assertEqual('Chris Corbyn <chris@swiftmailer.org>',
      $header->getPreparedValue()
      );
  }
  
  public function testHiddenAddressesAreStillReturnedByGetAddresses()
  {
    $header = $this->_getHeader('To',
      array(
        'chris@swiftmailer.org' => 'Chris Corbyn',
        'fred@swiftmailer.org' => 'Fred'
        )
      );
    $header->setHiddenAddresses('fred@swiftmailer.org');
    $this->assertEqual(
      array('chris@swiftmailer.org', 'fred@swiftmailer.org'),
      $header->getAddresses()
      );
  }
  
  public function testHiddenAddressesInGroups()
  {
    $header = $this->_getHeader('To');
    $header->defineGroup('undisclosed-recipients',
      array('tim@swiftmailer.org' => 'Tim Fletcher',
      'andrew@swiftmailer.org' => 'Andrew Rose',
      'chris@swiftmailer.org')
      );
    $header->setHiddenAddresses(
      array('tim@swiftmailer.org', 'andrew@swiftmailer.org', 'chris@swiftmailer.org')
      );
    $this->assertEqual(
      array('tim@swiftmailer.org', 'andrew@swiftmailer.org', 'chris@swiftmailer.org'),
      $header->getAddresses()
      );
    $this->assertEqual('undisclosed-recipients:;', $header->getPreparedValue());
  }
  
  public function testDefininingEntireGroupAsHidden()
  {
    /* -- RFC 2822, 3.4.
     Because the list of mailboxes can be empty, using the group construct
     is also a simple way to communicate to recipients that the message
     was sent to one or more named sets of recipients, without actually
     providing the individual mailbox address for each of those
     recipients.
     */
    
    $header = $this->_getHeader('To');
    $header->defineGroup('undisclosed-recipients',
      array('tim@swiftmailer.org' => 'Tim Fletcher',
      'andrew@swiftmailer.org' => 'Andrew Rose',
      'chris@swiftmailer.org'),
      true
      );
    $this->assertEqual(
      array('tim@swiftmailer.org', 'andrew@swiftmailer.org', 'chris@swiftmailer.org'),
      $header->getAddresses()
      );
    $this->assertEqual('undisclosed-recipients:;', $header->getPreparedValue());
  }
  
  public function testRemoveGroup()
  {
    $header = $this->_getHeader('To', 'fred@swiftmailer.org');
    $header->defineGroup('undisclosed-recipients',
      array('tim@swiftmailer.org' => 'Tim Fletcher',
      'andrew@swiftmailer.org' => 'Andrew Rose',
      'chris@swiftmailer.org')
      );
    $header->removeGroup('undisclosed-recipients');
    $this->assertEqual(
      array('fred@swiftmailer.org'),
      $header->getAddresses()
      );
    $this->assertEqual('fred@swiftmailer.org', $header->getPreparedValue());
  }
  
  public function testGroupNamedIsFormattedIfNeeded()
  {
    $header = $this->_getHeader('To');
    $header->defineGroup('Users, Admins', array('xyz@abc.com'));
    $this->assertEqual('"Users\\, Admins":xyz@abc.com;', $header->getPreparedValue());
  }
  
  public function testGroupNameIsEncodedIfNeeded()
  {
    $encoder = new Swift_Mime_MockHeaderEncoder();
    $encoder->setReturnValue('getName', 'Q');
    $encoder->expectOnce('encodeString',
      array('R' . pack('C', 0x8F) . 'bots', '*', '*')
      );
    $encoder->setReturnValue('encodeString', 'R=8Fbots');
    
    $header = $this->_getHeader('To', null, $encoder);
    $header->defineGroup('R' . pack('C', 0x8F) . 'bots', array('xyz@abc.com'));
    $this->assertEqual('=?utf-8?Q?R=8Fbots?=:xyz@abc.com;', $header->getPreparedValue());
  }
  
  public function testSetValueWithSimpleAddressList()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue('chris@swiftmailer.org, mark@swiftmailer.org');
    $this->assertEqual('chris@swiftmailer.org, mark@swiftmailer.org',
      $header->getPreparedValue()
      );
    $this->assertEqual(array('chris@swiftmailer.org', 'mark@swiftmailer.org'),
      $header->getAddresses()
      );
  }
  
  public function testSetValueWithNameAddressList()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue('Chris Corbyn <chris@swiftmailer.org>,' . "\r\n " .
      'Mark Corbyn <mark@swiftmailer.org>'
      );
    $this->assertEqual('Chris Corbyn <chris@swiftmailer.org>,' . "\r\n " .
      'Mark Corbyn <mark@swiftmailer.org>',
      $header->getPreparedValue()
      );
    $this->assertEqual(array('chris@swiftmailer.org', 'mark@swiftmailer.org'),
      $header->getAddresses()
      );
    $this->assertEqual(array(
      'chris@swiftmailer.org' => 'Chris Corbyn',
      'mark@swiftmailer.org' => 'Mark Corbyn'),
      $header->getNameAddresses()
      );
  }
  
  public function testSetValueWithEmptyGroup()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue('undisclosed-recipients:;');
    $this->assertEqual('undisclosed-recipients:;', $header->getPreparedValue());
    $this->assertEqual(array(), $header->getAddresses());
    $this->assertEqual(array(), $header->getGroup('undisclosed-recipients'));
  }
  
  public function testSetValueWithAddressesInGroup()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue('Brothers:chris@swiftmailer.org, mark@swiftmailer.org;');
    $this->assertEqual('Brothers:chris@swiftmailer.org, mark@swiftmailer.org;',
      $header->getPreparedValue()
      );
    $this->assertEqual(array('chris@swiftmailer.org', 'mark@swiftmailer.org'),
      $header->getAddresses()
      );
    $this->assertEqual(
      array('chris@swiftmailer.org' => null, 'mark@swiftmailer.org' => null),
      $header->getGroup('Brothers')
      );
  }
  
  public function testSetValueWithNameAddressesInGroup()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue(
      'Brothers:Chris Corbyn <chris@swiftmailer.org>,' . "\r\n " .
      'Mark Corbyn <mark@swiftmailer.org>;'
      );
    $this->assertEqual(
      'Brothers:Chris Corbyn <chris@swiftmailer.org>,' . "\r\n " .
      'Mark Corbyn <mark@swiftmailer.org>;',
      $header->getPreparedValue()
      );
    $this->assertEqual(array('chris@swiftmailer.org', 'mark@swiftmailer.org'),
      $header->getAddresses()
      );
    $this->assertEqual(
      array('chris@swiftmailer.org' => 'Chris Corbyn',
      'mark@swiftmailer.org' => 'Mark Corbyn'),
      $header->getNameAddresses()
      );
    $this->assertEqual(
      array('chris@swiftmailer.org' => 'Chris Corbyn',
      'mark@swiftmailer.org' => 'Mark Corbyn'),
      $header->getGroup('Brothers')
      );
  }
  
  public function testSetValueWithQuotedNameInGroup()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue(
      '"Corbyn\\, Brothers":Chris Corbyn <chris@swiftmailer.org>,' . "\r\n " .
      'Mark Corbyn <mark@swiftmailer.org>;'
      );
    $this->assertEqual(
      '"Corbyn\\, Brothers":Chris Corbyn <chris@swiftmailer.org>,' . "\r\n " .
      'Mark Corbyn <mark@swiftmailer.org>;',
      $header->getPreparedValue()
      );
    $this->assertEqual(array('chris@swiftmailer.org', 'mark@swiftmailer.org'),
      $header->getAddresses()
      );
    $this->assertEqual(
      array('chris@swiftmailer.org' => 'Chris Corbyn',
      'mark@swiftmailer.org' => 'Mark Corbyn'),
      $header->getNameAddresses()
      );
    $this->assertEqual(
      array('chris@swiftmailer.org' => 'Chris Corbyn',
      'mark@swiftmailer.org' => 'Mark Corbyn'),
      $header->getGroup('Corbyn, Brothers')
      );
  }
  
  public function testSetValueWithEncodedNameInGroup()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue(
      '=?utf-8?Q?Corbyn_Brothers?=:Chris Corbyn <chris@swiftmailer.org>,' . "\r\n " .
      'Mark Corbyn <mark@swiftmailer.org>;'
      );
    $this->assertEqual(
      '=?utf-8?Q?Corbyn_Brothers?=:Chris Corbyn <chris@swiftmailer.org>,' . "\r\n " .
      'Mark Corbyn <mark@swiftmailer.org>;',
      $header->getPreparedValue()
      );
    $this->assertEqual(array('chris@swiftmailer.org', 'mark@swiftmailer.org'),
      $header->getAddresses()
      );
    $this->assertEqual(
      array('chris@swiftmailer.org' => 'Chris Corbyn',
      'mark@swiftmailer.org' => 'Mark Corbyn'),
      $header->getNameAddresses()
      );
    $this->assertEqual(
      array('chris@swiftmailer.org' => 'Chris Corbyn',
      'mark@swiftmailer.org' => 'Mark Corbyn'),
      $header->getGroup('Corbyn Brothers')
      );
  }
  
  public function testSetValueWithCombinedMailboxListsAndGroups()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue(
      'foo@bar.com, Tim Fletcher <tim@swiftmailer.org>,' . "\r\n " .
      'Corbyn Brothers:Chris Corbyn <chris@swiftmailer.org>,' . "\r\n " .
      'Mark Corbyn <mark@swiftmailer.org>;, Andrew Rose <andrew@swiftmailer.org>'
      );
    $this->assertEqual(
      'foo@bar.com, Tim Fletcher <tim@swiftmailer.org>,' . "\r\n " .
      'Corbyn Brothers:Chris Corbyn <chris@swiftmailer.org>,' . "\r\n " .
      'Mark Corbyn <mark@swiftmailer.org>;, Andrew Rose <andrew@swiftmailer.org>',
      $header->getPreparedValue()
      );
    $this->assertEqual(
      array(
        'foo@bar.com', 'tim@swiftmailer.org', 'andrew@swiftmailer.org',
        'chris@swiftmailer.org', 'mark@swiftmailer.org'
        ),
      $header->getAddresses()
      );
    $this->assertEqual(
      array(
        'foo@bar.com' => null, 'tim@swiftmailer.org' => 'Tim Fletcher',
        'andrew@swiftmailer.org' => 'Andrew Rose',
        'chris@swiftmailer.org' => 'Chris Corbyn',
        'mark@swiftmailer.org' => 'Mark Corbyn'
        ),
      $header->getNameAddresses()
      );
    $this->assertEqual(
      array('chris@swiftmailer.org' => 'Chris Corbyn',
      'mark@swiftmailer.org' => 'Mark Corbyn'),
      $header->getGroup('Corbyn Brothers')
      );
  }
  
  public function testSetValueAcceptsGroupWithCommentBody()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue(
      'foo@bar.com, Tim Fletcher <tim@swiftmailer.org>,' . "\r\n " .
      'Corbyn Brothers:(addresses undisclosed);, Andrew Rose <andrew@swiftmailer.org>'
      );
    $this->assertEqual(
      'foo@bar.com, Tim Fletcher <tim@swiftmailer.org>,' . "\r\n " .
      'Corbyn Brothers:(addresses undisclosed);, Andrew Rose <andrew@swiftmailer.org>',
      $header->getPreparedValue()
      );
    $this->assertEqual(
      array('foo@bar.com', 'tim@swiftmailer.org', 'andrew@swiftmailer.org'),
      $header->getAddresses()
      );
    $this->assertEqual(
      array(
        'foo@bar.com' => null, 'tim@swiftmailer.org' => 'Tim Fletcher',
        'andrew@swiftmailer.org' => 'Andrew Rose'
        ),
      $header->getNameAddresses()
      );
    $this->assertEqual(array(), $header->getGroup('Corbyn Brothers'));
  }
  
  public function testSetValueAcceptsEmptyFieldBody()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue('');
    $this->assertEqual('', $header->getPreparedValue());
    $this->assertEqual(array(), $header->getAddresses());
    $this->assertEqual(array(), $header->getNameAddresses());
  }
  
  public function testSetValueAcceptsCommentFieldBody()
  {
    $header = $this->_getHeader('Bcc');
    $header->setPreparedValue('(hidden from display)');
    $this->assertEqual('(hidden from display)', $header->getPreparedValue());
    $this->assertEqual(array(), $header->getAddresses());
    $this->assertEqual(array(), $header->getNameAddresses());
  }
  
  public function testSetValueWithCommasInQuotedString()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue(
      '"Chris Corbyn, DHE" <chris@swiftmailer.org>,' . "\r\n " .
      '"Mark Corbyn, BSc Comp. Sci." <mark@swiftmailer.org>'
      );
    
    $this->assertEqual(
      '"Chris Corbyn, DHE" <chris@swiftmailer.org>,' . "\r\n " .
      '"Mark Corbyn, BSc Comp. Sci." <mark@swiftmailer.org>',
      $header->getPreparedValue()
      );
    $this->assertEqual(
      array('chris@swiftmailer.org', 'mark@swiftmailer.org'),
      $header->getAddresses()
      );
    $this->assertEqual(
      array('chris@swiftmailer.org' => 'Chris Corbyn, DHE',
      'mark@swiftmailer.org' => 'Mark Corbyn, BSc Comp. Sci.'),
      $header->getNameAddresses()
      );
    $this->assertEqual(
      array('"Chris Corbyn\\, DHE" <chris@swiftmailer.org>',
      '"Mark Corbyn\\, BSc Comp\\. Sci\\." <mark@swiftmailer.org>'),
      $header->getNameAddressStrings()
      );
  }
  
  public function testSetValueWithAddrSpecInQuotedString()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue(
      '"Chris <c@a.b> Corbyn" <chris@swiftmailer.org>'
      );
    
    $this->assertEqual(
      '"Chris <c@a.b> Corbyn" <chris@swiftmailer.org>',
      $header->getPreparedValue()
      );
    $this->assertEqual(array('chris@swiftmailer.org'), $header->getAddresses());
    $this->assertEqual(
      array('chris@swiftmailer.org' => 'Chris <c@a.b> Corbyn'),
      $header->getNameAddresses()
      );
    $this->assertEqual(
      array('"Chris \\<c\\@a\\.b\\> Corbyn" <chris@swiftmailer.org>'),
      $header->getNameAddressStrings()
      );
  }
  
  public function testSetValueWithGroupInQuotedString()
  {
    $header = $this->_getHeader('To');
    $header->setPreparedValue(
      '"Chris <c@a.b>, Foo:<x@y.z>;, <c@d.e> Corbyn" <chris@swiftmailer.org>'
      );
    
    $this->assertEqual(
      '"Chris <c@a.b>, Foo:<x@y.z>;, <c@d.e> Corbyn" <chris@swiftmailer.org>',
      $header->getPreparedValue()
      );
    $this->assertEqual(array('chris@swiftmailer.org'), $header->getAddresses());
    $this->assertEqual(
      array('chris@swiftmailer.org' => 'Chris <c@a.b>, Foo:<x@y.z>;, <c@d.e> Corbyn'),
      $header->getNameAddresses()
      );
    $this->assertEqual(
      array('"Chris \\<c\\@a\\.b\\>\\, Foo\\:\\<x\\@y\\.z\\>\\;\\, \\<c\\@d\\.e\\> Corbyn" <chris@swiftmailer.org>'),
      $header->getNameAddressStrings()
      );
  }
  
  // -- Private methods
  
  private function _getHeader($name, $value = null, $encoder = null)
  {
    return new Swift_Mime_Header_AddressHeader(
      $name, $value, $this->_charset, $encoder
      );
  }
  
}
