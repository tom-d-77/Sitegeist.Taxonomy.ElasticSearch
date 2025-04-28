<?php
namespace Sitegeist\Taxonomy\ElasticSearch\Eel;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Sitegeist\Taxonomy\Service\TaxonomyService;
use Neos\Eel\ProtectedContextAwareInterface;

class TaxonomyIndexingHelper implements ProtectedContextAwareInterface
{

    /**
     * @var TaxonomyService
     * @Flow\Inject
     */
    protected $taxonomyService;
    #[\Neos\Flow\Annotations\Inject]
    protected \Neos\ContentRepositoryRegistry\ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @param \Neos\ContentRepository\Core\Projection\ContentGraph\Node|\Neos\ContentRepository\Core\Projection\ContentGraph\Node[] $taxonomies
     * @return array
     */
    public function extractIdentifierAndParentIdentifiers($taxonomies)
    {

        if (!$taxonomies) {
            return [];
        }

        if ($taxonomies instanceof \Neos\ContentRepository\Core\Projection\ContentGraph\Node) {
            $taxonomies = [$taxonomies];
        }

        $identifiers = [];
        $taxonomyNodeType = $this->taxonomyService->getTaxonomyNodeType();

        foreach ($taxonomies as $taxonomy) {
            $contentRepository = $this->contentRepositoryRegistry->get($taxonomy->contentRepositoryId);
            if (($taxonomy instanceof \Neos\ContentRepository\Core\Projection\ContentGraph\Node) && $contentRepository->getNodeTypeManager()->getNodeType($taxonomy->nodeTypeName)->isOfType($taxonomyNodeType)) {
                $identifier = $taxonomy->aggregateId->value;
                $identifiers[$identifier] = $identifier;
                $subgraph = $this->contentRepositoryRegistry->subgraphForNode($taxonomy);
                $parent = $subgraph->findParentNode($taxonomy->aggregateId);
                $contentRepository = $this->contentRepositoryRegistry->get($parent->contentRepositoryId);
                while ($parent && ($parent instanceof \Neos\ContentRepository\Core\Projection\ContentGraph\Node) && $contentRepository->getNodeTypeManager()->getNodeType($parent->nodeTypeName)->isOfType($taxonomyNodeType)) {
                    $identifier = $parent->aggregateId->value;
                    $identifiers[$identifier] = $identifier;
                    $subgraph = $this->contentRepositoryRegistry->subgraphForNode($parent);
                    $parent = $subgraph->findParentNode($parent->aggregateId);
                }
            }
        }

        return array_keys($identifiers);
    }

    /**
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
