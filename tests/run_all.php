<?php

/*
 * tests/run_all.php
 *
 * Roda os testes unitários e depois os de integração, na ordem certa
 * (unitários primeiro porque são mais rápidos e não dependem do
 * servidor estar de pé -- se eles já falharem, nem vale a pena testar
 * o resto).
 *
 * Como rodar:
 *   C:\php\php.exe tests\run_all.php
 *
 * Pra também testar login e o suporte com IA (não só as rotas que não
 * pedem login), defina a senha da conta de teste antes:
 *   set SADAG_TEST_PASSWORD=sua_senha_aqui
 *   C:\php\php.exe tests\run_all.php
 *
 * Sem isso, esses testes são pulados (avisando na tela) em vez de
 * falhar -- não travam a suíte, só mostram menos cobertura.
 */

$inicio = microtime(true);

echo "############################################\n";
echo "# 1/2 -- TESTES UNITARIOS\n";
echo "############################################\n\n";
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/unit_tests.php'), $codeUnit);

echo "\n\n############################################\n";
echo "# 2/2 -- TESTES DE INTEGRACAO (servidor precisa estar rodando)\n";
echo "############################################\n\n";
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/integration_tests.php'), $codeIntegration);

$tempo = round(microtime(true) - $inicio, 1);

echo "\n\n############################################\n";
if ($codeUnit === 0 && $codeIntegration === 0) {
    echo "RESULTADO GERAL: TUDO PASSOU (em {$tempo}s)\n";
    exit(0);
}
echo "RESULTADO GERAL: HA TESTES FALHANDO (em {$tempo}s) -- veja o detalhe acima\n";
exit(1);
