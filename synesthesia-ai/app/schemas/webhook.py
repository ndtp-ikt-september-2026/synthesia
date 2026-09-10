from typing import Any

from pydantic import BaseModel, Field


class ProductData(BaseModel):
    title: str
    description: str | None = None
    category_id: int | None = None
    status: int = 1
    entity_type: str | None = None
    track_ids: list[int] | None = None
    attributes: dict[str, Any] = Field(default_factory=dict)


class ProductSyncRequest(BaseModel):
    event: str
    product_id: int
    data: ProductData | None = None


class ProductSyncResponse(BaseModel):
    status: str = 'accepted'
    message: str = 'Sync task queued'
