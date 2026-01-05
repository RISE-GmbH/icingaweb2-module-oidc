# Azure Configuration <a id="module-oidc-azure"></a>

The Azure configuration in Microsoft 356 should look like the following:


![01-azure-m365-admin-center.png](img/azure/01-azure-m365-admin-center.png)

* Click on Azure

![02-azure-admin-dashboard.png](img/azure/02-azure-admin-dashboard.png)

* Click on Microsoft Entra ID

![03-azure-overview.png](img/azure/03-azure-overview.png)

* Start your App Registration Process

![04-azure-app-registration.png](img/azure/04-azure-app-registration.png)

* If you name in the oidc module is azure your url should look like this:
> https://your-icingaweb2-instance/icingaweb2/oidc/authentication/realm?name=azure

![05-azure-app-registered.png](img/azure/05-azure-app-registered.png)

* write down your Application ID you need to use this in your IcingaWeb2 OIDC provider settings

![06-azure-secret-key.png](img/azure/06-azure-secret-key.png)

Generate a secret key

![07-azure-secret-key-generate.png](img/azure/07-azure-secret-key-generate.png)


![08-azure-secret-key-view.png](img/azure/08-azure-secret-key-view.png)

* Write down the value you need to use this in your IcingaWeb2 OIDC provider settings

![09-azure-endpoints.png](img/azure/09-azure-endpoints.png)

* look for the Endpoints Menu Item

![10-azure-copy-endpoint.png](img/azure/10-azure-copy-endpoint.png)

* Copy the first URL you need to use this in your IcingaWeb2 OIDC provider settings

![11-azure-api-permissions.png](img/azure/11-azure-api-permissions.png)

* Got to the API Permissions section and add new permissions

![12-azure-grant-permissions.png](img/azure/12-azure-grant-permissions.png)

* Select all permissions as shown in this picture.

![13-azure-optional.png](img/azure/13-azure-optional.png)

* Goto the token Configuration and add additional requests.

![14-groups-optional-groups.png](img/azure/14-groups-optional-groups.png)

* Here we want the groups by samaccountname

![15-azure-grant-access.png](img/azure/15-azure-grant-access.png)

* Since this need additional grants, click on the check sign and grant the access.

![16-azure-remove-access.png](img/azure/16-azure-remove-access.png)

* If you want you can remove the parts the user has access to anyway.

![17-azure-oidc-module.png](img/azure/17-azure-oidc-module.png)
* now we add the URL from the Endpoint window
* the generated secret as secret
* the Application ID as Appname
* tick azure groups
* tick the no OIDC groups Request