<b>{{__ 'Warning'}}</b><br><br>
{{__ 'You are about to delete the class'}} <b>{{name}}</b>, {{__ 'which contains assets that are in use elsewhere.'}}
<br>
List of assets currently in use: <br>
<ul>
    {{#each items}}<li>{{this.label}}</li>{{/each}}
</ul>
