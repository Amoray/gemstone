gemstone
========
A small and lightweight project I'm working on to play around with a database abstraction layer.

How to use
----------

### Mining
Pass the mine a name, it will return a stone that references a table with the name passed

```
$book = G::mine('books');
```

### Parameters
You can add as many parameters to a stone as you'd like.

```
$book   ->fname("Douglas")
		->lname("Adams")
		->title("Hitchhiker's Guide to the Galaxy")
```

### Synchronize
Have a need that two tables must both be written to at the same time. Sync allows you to submit all or none.

```
$booktwo->fname("Douglas")
		->lname("Adams")
		->title("Derk Gently's Holistic Detective Agency")
;

$book->sync($booktwo);
```	

### Save
You can save or deposit a stone at any time like so, at this time any stones set to sync will be sent to the server as a transaction. If any part of the transaction fails the database will be rolled back to before the transaction.

```
G::deposit($book);
```