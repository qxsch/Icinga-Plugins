# Icinga

This plugin serves as a wrapper arround a noisy plugin. It extracts the important information from the following strings:

  *  ```<icingaoutput>.....</icingaoutput>```        Mandantory: the output, that should be returned
  *  ```<icingareturncode>...</icingareturncode>```  Optional: the return code (takes the return code of the executed plugin, in case this output is missing)
