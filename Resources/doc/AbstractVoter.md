BaseAdminBundle
=====================
 
[Getting started](getting_started.md#BaseAdminBundle)
| [CrudController](CrudController.md#BaseAdminBundle)
| [Router](Router.md#BaseAdminBundle)
| **AbstractVoter**
| [AbstractDatatable](AbstractDatatable.md#BaseAdminBundle)
| [TwigComponents](TwigComponents.md#BaseAdminBundle)

## AbstractVoter
The AbstractVoter offers an alternative to Symfony's abstract Voter class:
it adds a few methods and takes the [strategy](https://symfony.com/doc/4.2/security/voters.html#changing-the-access-decision-strategy)
 into account.

### Simplify retrieving user/role information

When using voters, most likely you'll need to check for roles or if you're dealing with a particular user. It quickyl becomes
annoying when you need to parse the token for practically every custom method you make. 
Therefore when `vote` is being invoked using the AbstractVoter, the token will be set as class-property instead of being
passed as argument to `voteOnAttribute`. This simplifies using methods such as
`$this->isUser()` without having to pass the token object.

Additionally, some obvious methods are added:
    `isUser`, `isAdmin`, `isSuperAdmin`, `getUser`, `hasRole(string $roleName)`


### Optional `supports` and allow null result from `voteOnAttribute` 

Implementing the `supports` method can be bothersome: most likely you'll add
an `in_array` check where all the attributes vor this voter class are being checked.
(see [symfony documentation about creating-the-custom-voter](https://symfony.com/4.2/master/security/voters.html#creating-the-custom-voter))

When defining attribute-constants in the voter, it might be a good idea to make sure these are unique.
For example, in a ProductVoter using 'product_edit' instead of 'edit' makes it easier to recognize this
attribute when used elsewhere.

Once you make sure all attributes are unique (at least compared to other voters), the supports check 
can become redundant: the `voteOnAttribute` should already check for the appropriate attribute, so
you may not need to check attributes twice.

Normally, the `voteOnAttribute` would expect a `true` or `false`, but to account for 'abstain', the
AbstractVoter allows a `null` response as well. 

### Strategy-problem with Symfony's abstract Voter class.

Let's say you are using `IsGranted(['ATTR_A_1', 'ATTR_A_2', 'ATTR_B_1'])`, where
- ATTR_A_1 belongs to VoterA
- ATTR_A_2 also belongs to VoterA
- ATTR_B_1 belongs to VoterB

 Then you get the following scenarios:

|            | ATTR_A_1  | ATTR_A_2  | ATTR_B_1 | affirmative | unanimous  
| ---------- |:---------:|:---------:|:--------:|:-----------:|:----------:
| Scenario A | true      | true      | true     | granted     | granted    
| Scenario B | true      | true      | false    | granted     | denied     
| Scenario C | true      | false     | false    | granted     | denied     
| Scenario D | false     | false     | false    | denied      | denied     
| Scenario E | false     | false     | true     | denied      | denied     
| Scenario F | false     | true      | true     | granted     | **granted**

If you're unaware of the fact that the abstract Voter class doesn't take strategy into account, scenario F would 
yield an outcome you might not expect:
Since ATTR_A_1 and ATTR_A_2 are both VoterA and the abstract voter only checks if any attribute returns true, VoterA will
return access granted. In turn, the access decision manager only sees that VoterA and VoterB both grant access.

> **Note:** when using two attributes of the same Voter withing one is_granted check you might want
> to consider creating a new attribute that does both these checks in one single go.

### Solution

Through dependency injection the AbstractVoter knows what strategy is being used. 
In case of the unanimous strategy in scenario F would return denied.


> **Note:**  There is another strategy, the `consensus`. This strategy would also yield an unexpected outcome using the original abstract
Voter class. However, the AbstractVoter only solves this problem when attributes of one Voter are being used at a time, since
it cannot tell the access decision manager how many attributes would return true.  
