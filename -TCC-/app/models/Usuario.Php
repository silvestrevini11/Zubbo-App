<!--O Model seria responsável pela operação relacionada ao usuário.
    Por exemplo:
        app/models/Usuario.php
        Ele poderia ter uma função/método para cadastrar:
                Usuario
                    ↓
                cadastrar()
                    ↓
            INSERT INTO Usuario
        Assim, o SQL fica concentrado no Model.
        Isso vai ser muito útil depois, porque seu sistema provavelmente terá: -->