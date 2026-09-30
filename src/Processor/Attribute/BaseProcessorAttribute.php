<?php

declare(strict_types=1);

namespace KaririCode\Contract\Processor\Attribute;

/**
 * Classe BaseProcessorAttribute.
 *
 * Classe abstrata que implementa atributos de processamento, fornecendo funcionalidades
 * para registrar processadores e mensagens customizadas associadas a cada um. Esta classe
 * é projetada para servir como base para a criação de atributos de processamento que
 * suportam personalização de mensagens.
 *
 * @category  Processor
 */
abstract class BaseProcessorAttribute implements ProcessableAttribute, CustomizableMessageAttribute
{
    /**
     * @var array Lista de processadores associados ao atributo.
     *            Somente processadores válidos são armazenados nesta lista.
     */
    private readonly array $processors;

    /**
     * @var array Lista de mensagens customizadas para os processadores.
     *            As mensagens são associadas ao nome do processador e são opcionais.
     */
    private readonly array $messages;

    /**
     * Construtor da classe BaseProcessorAttribute.
     *
     * Inicializa a lista de processadores e, opcionalmente, as mensagens customizadas.
     * Os processadores inválidos (nulos ou false) são automaticamente filtrados.
     *
     * @param array $processors Lista de processadores a serem associados ao atributo
     * @param array|null $messages (Opcional) Lista de mensagens customizadas associadas aos processadores
     */
    public function __construct(array $processors, ?array $messages = null)
    {
        $this->processors = self::filterValidProcessors($processors);
        $this->messages = $messages ?? [];
    }

    /**
     * Retorna a lista de processadores associados ao atributo.
     *
     * @return array A lista de processadores válidos
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    /**
     * Obtém uma mensagem customizada para um processador específico, caso esteja definida.
     *
     * @param string $processorName O nome do processador para o qual a mensagem é requisitada
     *
     * @return string|null A mensagem customizada associada ao processador, ou null se não existir
     */
    public function getMessage(string $processorName): ?string
    {
        return $this->messages[$processorName] ?? null;
    }

    /**
     * Filtra e retorna somente os processadores válidos.
     *
     * Este método estático remove processadores nulos ou definidos como false,
     * garantindo que apenas processadores utilizáveis sejam armazenados.
     *
     * @param array $processors Lista de processadores a serem filtrados
     *
     * @return array A lista de processadores válidos
     */
    private static function filterValidProcessors(array $processors): array
    {
        return array_filter($processors, static fn ($v) => null !== $v && false !== $v);
    }
}
