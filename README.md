# Installation

Via Cockpit CLI (Cockpit 0.9.1 or greater):
```
./cp install/addon --name CockpitQL
```

Manually:
- download the repository;
- add it as a subfolder to the `./addons` directory.

Either way, the final tree should look as follows:
```
user@pc:/path/to/cockpit/folder$ tree -d -L 2 -n
.
├── addons
│   └── CockpitQL
...
```

# Api

GraphQL entry point:

```
/api/graphql/query?token=*apitoken*
```

# Example query:

```
{
  collection(name:"posts", filter:{published:true})
}
```

Assume we have a collection named `posts`, you can also query like this

```
{
  postsCollection(filter:{published:true}){
    _id,
    title
    content,
    image{
      path
    }
  }
}
```


### 💐 SPONSORED BY

[![ginetta](https://user-images.githubusercontent.com/321047/29219315-f1594924-7eb7-11e7-9d58-4dcf3f0ad6d6.png)](https://www.ginetta.net)<br>
We create websites and apps that click with users.
