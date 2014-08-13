Code in the EllisLab Namespace
==============================


Controllers
===========

Controllers are the routing code.  They accept input, determine which models,
services and libraries we need.  Instantiate or query for those models,
services and libraries.  Assemble them.  Retrieve the output and then build the
view.  They should aim for minimal code internal to themselves.  If you find
yourself with a controller action pushing hundred lines or more consider
pulling that code out in to one or more new services.

Services
========

Services are essentially libraries that _belong to_ ExpressionEngine.  Meaning
that it wouldn't necessarily make sense to remove them from ExpressionEngine
and insert them in to some other program.  Whether coupled or not, they do
something that is specific to ExpressionEngine and what ExpressionEngine does.
You should always aim to make a service *as decoupled as humanly possible* but
services may be coupled in cases where decoupling isn't possible.  Services may
use and be aware of models or other services.  In many ways, services are
essentially reusable controllers.

Models
======

Models are our data.   Where Libraries and Services are primarily behavior with
some data, Models are primarily data with some behavior.


Libraries
=========

Libraries are completely decoupled from ExpressionEngine and do not belong to
it.  They are things that you could potentially pull out from ExpressionEngine
and drop into another program and they would make perfect sense.  They are
coupled to ExpresionEngine only through use.  ExpressionEngine uses them, but
they do not use each other or anything that belongs to ExpressionEngine --
Services, Models, etc.
