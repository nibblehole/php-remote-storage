<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Exception\RemoteStorageException;
use fkooman\OAuth\ResourceServer\TokenIntrospection;

class RemoteStorage
{
    /** @var fkooman\RemoteStorage\Metadata */
    private $md;

    /** @var fkooman\RemoteStorage\Document */
    private $d;

    /** @var fkooman\OAuth\ResourceServer\TokenIntrospection */
    private $i;

    public function __construct(Metadata $md, Document $d, TokenIntrospection $i)
    {
        $this->md = $md;
        $this->d = $d;
        $this->i = $i;
    }

    public function putDocument(Path $p, $type, $data, $ifMatch = null)
    {
        if ($p->getUserId() !== $this->i->getSub()) {
            throw new RemoteStorageException("not allowed");
        }

        if (null !== $ifMatch) {
            if ($ifMatch !== $this->md->getVersion($p)) {
                throw new RemoteStorageException("version mismatch");
            }
        }

        $updatedEntities = $this->d->putDocument($p, $data);
        foreach ($updatedEntities as $u) {
            $this->md->updateFolder(new Path($u));
        }
    }

    public function deleteDocument(Path $p, $ifMatch = null)
    {
        if ($p->getUserId() !== $i->getSub()) {
            throw new RemoteStorageException("not allowed");
        }

        if (null !== $ifMatch) {
            if ($ifMatch !== $this->md->getVersion($p)) {
                throw new RemoteStorageException("version mismatch");
            }
        }

        $deletedEntities = $this->d->deleteDocument($p);
        foreach ($deletedEntities as $d) {
            $this->md->deleteEntry(new Path($d));
        }
        // FIXME: increment the version of the folder containing the last
        // deleted folder
    }

    public function getDocument(Path $p)
    {
        if (!$p->getIsPublic()) {
            if ($p->getUserId() !== $this->i->getSub()) {
                throw new RemoteStorageException("not allowed");
            }
        }
        // FIXME: deal with ifMatch, version!
        return array(
            "data" => $this->d->getDocument($p),
            "version" => $this->md->getVersion($p)
        );
    }
}
