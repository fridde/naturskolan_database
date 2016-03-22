# Mailchimp

[Mailchimp](http://mailchimp.com/login) �r v�rt mejlsystem som vi anv�nder f�r flera �ndam�l.

P� Mailchimp
* finns listan �ver l�rare och rektorer samt deras kontaktuppgifter och skoltillh�righet. 
* kan vi skicka ut massmejl till en filtrerad lista av l�rare/rektorer som inneh�ller inneh�ll anpassad efter mottagaren. Till exempel blir 'Hej *|FNAME|*!' till 'Hej Ingela!', 'Hej Marie!', etc.
* finns det n�gra bra feedback-funktioner, t ex ser vi om en mottagare har l�st sitt mejl eller klickat p� en l�nk i mejlet

## Mailchimp API
List members can be members of certain (interest-)groups. A group is part of a category.
Example: A _category_ could be *Countries visited*. Then groups could be *Spain*, *Denmark*, *Japan* and *Mongolia*. The groups are defined by the admin and can't be added by the members.

In the response from the API-request ''get('lists/1ff7412fc8/members''' every member of the list has the item 'interests' given as an array. The keys of this array are the id's of the *groups* and it is not trivial to find out which categories they belong to. 