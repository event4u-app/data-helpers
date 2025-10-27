<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Pipeline;

use Exception;

/**
 * Pipeline for processing data through multiple stages.
 *
 * This pipeline allows you to chain multiple processing stages together,
 * including transformers, normalizers, validators, and custom stages.
 *
 * Example:
 *   $pipeline = new DtoPipeline();
 *   $pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int'])));
 *   $pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));
 *   $pipeline->addStage(new ValidationStage());
 *   $data = $pipeline->process($data);
 */
class DtoPipeline
{
    /** @var array<PipelineStageInterface> */
    private array $stages = [];

    /** @var array<string, mixed> */
    private array $context = [];

    private bool $stopOnError = true;

    /** Add a stage to the pipeline. */
    public function addStage(PipelineStageInterface $stage): self
    {
        $this->stages[] = $stage;

        return $this;
    }

    /**
     * Set whether to stop on error.
     *
     * @param bool $stopOnError If true, pipeline stops on first error. If false, continues processing.
     */
    public function stopOnError(bool $stopOnError): self
    {
        $this->stopOnError = $stopOnError;

        return $this;
    }

    /**
     * Process data through all stages.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws Exception If a stage fails and stopOnError is true
     */
    public function process(array $data): array
    {
        foreach ($this->stages as $stage) {
            try {
                $data = $stage->process($data);
                $this->context[$stage->getName()] = ['status' => 'success'];
            } catch (Exception $e) {
                $this->context[$stage->getName()] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];

                if ($this->stopOnError) {
                    throw $e;
                }
            }
        }

        return $data;
    }

    /**
     * Get all stages in the pipeline.
     *
     * @return array<PipelineStageInterface>
     */
    public function getStages(): array
    {
        return $this->stages;
    }

    /** Clear all stages from the pipeline. */
    public function clear(): self
    {
        $this->stages = [];
        $this->context = [];

        return $this;
    }

    /** Set whether to stop on error. */
    public function setStopOnError(bool $stopOnError): self
    {
        $this->stopOnError = $stopOnError;

        return $this;
    }

    /**
     * Get the pipeline context (execution results).
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /** Clear the pipeline context. */
    public function clearContext(): self
    {
        $this->context = [];

        return $this;
    }
}
